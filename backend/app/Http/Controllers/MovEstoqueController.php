<?php

namespace App\Http\Controllers;

use App\Models\MovEstoque;
use App\Models\ProdutoVariacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovEstoqueController extends Controller
{
    // GET /estoque/movimentacoes?id_variacao=X&tipo=Y&data_inicio=X&data_fim=Y — RF_B03
    public function index(Request $request)
    {
        $query = MovEstoque::with(['variacao.produto', 'usuario']);

        if ($request->has('id_variacao')) {
            $query->where('id_variacao', $request->id_variacao);
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('data_inicio')) {
            $query->whereDate('criado_em', '>=', $request->data_inicio);
        }

        if ($request->has('data_fim')) {
            $query->whereDate('criado_em', '<=', $request->data_fim);
        }

        return response()->json($query->orderBy('criado_em', 'desc')->get());
    }

    // GET /estoque/variacoes — RF_F04 (consulta por variação com estoque)
    public function variacoes(Request $request)
    {
        $query = ProdutoVariacao::with(['produto.categoria'])
            ->where('ativo', true);

        if ($request->has('id_produto')) {
            $query->where('id_produto', $request->id_produto);
        }

        // Estoque crítico (zerado ou abaixo do mínimo) — indicador 3 do Dashboard (RF_S04)
        if ($request->has('critico')) {
            $query->where(function ($q) {
                $q->where('qtd_estoque', '<=', 0);
            });
        }

        return response()->json($query->orderBy('qtd_estoque')->get());
    }

    // POST /estoque/ajuste — ajuste manual de estoque (RF_B03)
    public function ajuste(Request $request)
    {
        $validated = $request->validate([
            'id_variacao' => 'required|integer|exists:produto_variacao,id_variacao',
            'id_usuario'  => 'nullable|integer|exists:usuario,id_usuario',
            'tipo'        => 'required|string|in:entrada,saida,ajuste,perda',
            'quantidade'  => 'required|integer|min:1',
            'motivo'      => 'required|string',
        ]);

        $variacao = ProdutoVariacao::findOrFail($validated['id_variacao']);

        // Validar estoque antes de qualquer escrita
        if (in_array($validated['tipo'], ['saida', 'perda'])) {
            if ($variacao->qtd_estoque < $validated['quantidade']) {
                return response()->json(['message' => 'Quantidade supera o estoque disponível.'], 422);
            }
        }

        $result = DB::transaction(function () use ($validated, $variacao) {
            $mov = MovEstoque::create($validated);

            if (in_array($validated['tipo'], ['entrada', 'ajuste'])) {
                $variacao->increment('qtd_estoque', $validated['quantidade']);
            } else {
                $variacao->decrement('qtd_estoque', $validated['quantidade']);
            }

            return [
                'movimentacao' => $mov->fresh(['variacao.produto']),
                'variacao'     => $variacao->fresh(),
            ];
        });

        return response()->json($result, 201);
    }
}
