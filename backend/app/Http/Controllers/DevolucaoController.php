<?php

namespace App\Http\Controllers;

use App\Models\CreditoLoja;
use App\Models\Devolucao;
use App\Models\ItemDevolucao;
use App\Models\MovEstoque;
use App\Models\ProdutoVariacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevolucaoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_venda'   => 'required|integer|exists:venda,id_venda',
            'id_cliente' => 'nullable|integer|exists:cliente,id_cliente',
            'id_usuario' => 'nullable|integer|exists:usuario,id_usuario',
            'tipo'       => 'required|string|in:venda,condicional,excecao',
            'observacao' => 'nullable|string',
        ]);

        // id_condicional permanece null nesta fase
        // data_devolucao gerado pelo banco (DEFAULT_GENERATED)
        $devolucao = Devolucao::create([
            'id_venda'   => $validated['id_venda'],
            'id_cliente' => $validated['id_cliente'] ?? null,
            'id_usuario' => $validated['id_usuario'] ?? null,
            'tipo'       => $validated['tipo'],
            'observacao' => $validated['observacao'] ?? null,
        ]);

        return response()->json($devolucao->fresh(['venda', 'cliente', 'usuario']), 201);
    }

    public function addItem(Request $request, Devolucao $devolucao)
    {
        $validated = $request->validate([
            'id_variacao'   => 'required|integer|exists:produto_variacao,id_variacao',
            'quantidade'    => 'required|integer|min:1',
            'valor_unitario'=> 'required|numeric|min:0',
            'descricao_item'=> 'nullable|string',
        ]);

        try {
        $result = DB::transaction(function () use ($validated, $devolucao) {
            $item = ItemDevolucao::create([
                'id_devolucao'  => $devolucao->id_devolucao,
                'id_variacao'   => $validated['id_variacao'],
                'quantidade'    => $validated['quantidade'],
                'valor_unitario'=> $validated['valor_unitario'],
                'descricao_item'=> $validated['descricao_item'] ?? null,
            ]);

            $variacao = ProdutoVariacao::find($validated['id_variacao']);
            $variacao->increment('qtd_estoque', $validated['quantidade']);

            MovEstoque::create([
                'id_variacao' => $validated['id_variacao'],
                'tipo'        => 'devolucao',
                'quantidade'  => $validated['quantidade'],
                'motivo'      => 'Devolucao item venda',
            ]);

            // valor_saldo: STORED GENERATED — não inserir
            // valor_utilizado: default=0.00, status: default='disponivel' — banco aplica
            $credito = CreditoLoja::create([
                'id_devolucao'  => $devolucao->id_devolucao,
                'id_cliente'    => $devolucao->id_cliente,
                'origem'        => 'devolucao',
                'valor_original'=> $validated['quantidade'] * $validated['valor_unitario'],
            ]);

            $credito->refresh();

            return [
                'item'      => $item,
                'devolucao' => $devolucao->fresh(['venda', 'cliente', 'usuario', 'itens']),
            ];
        });

        return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno.', 'error' => $e->getMessage()], 500);
        }
    }
}
