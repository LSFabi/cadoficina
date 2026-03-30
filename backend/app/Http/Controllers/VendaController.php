<?php

namespace App\Http\Controllers;

use App\Models\ItemVenda;
use App\Models\Venda;
use Illuminate\Http\Request;

class VendaController extends Controller
{
    public function index()
    {
        return response()->json(
            Venda::with(['cliente', 'usuario'])->get()
        );
    }

    public function show(Venda $venda)
    {
        return response()->json(
            $venda->load(['cliente', 'usuario'])
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cliente' => 'required|integer|exists:cliente,id_cliente',
            'id_usuario' => 'nullable|integer|exists:usuario,id_usuario',
            'desconto'   => 'numeric|min:0',
        ]);

        $venda = Venda::create([
            'id_cliente'  => $validated['id_cliente'],
            'id_usuario'  => $validated['id_usuario'] ?? null,
            'desconto'    => $validated['desconto'] ?? 0,
            'valor_total' => 0,
            'status'      => 'rascunho',
        ]);

        return response()->json($venda->load(['cliente', 'usuario']), 201);
    }

    public function addItem(Request $request, Venda $venda)
    {
        if ($venda->status !== 'rascunho') {
            return response()->json([
                'message' => 'Itens só podem ser adicionados a vendas em status rascunho.',
            ], 422);
        }

        $validated = $request->validate([
            'id_variacao'    => 'required|integer|exists:produto_variacao,id_variacao',
            'quantidade'     => 'required|integer|min:1',
            'preco_unitario' => 'required|numeric|min:0',
        ]);

        // subtotal é STORED GENERATED no banco (quantidade × preco_unitario) — não inserir.
        // O trigger trg_item_venda_before_insert (RN02) valida estoque e variacao ativa.
        // O trigger trg_item_venda_after_insert deduz estoque e registra mov_estoque.
        $item = ItemVenda::create([
            'id_venda'       => $venda->id_venda,
            'id_variacao'    => $validated['id_variacao'],
            'quantidade'     => $validated['quantidade'],
            'preco_unitario' => $validated['preco_unitario'],
        ]);

        $novoTotal = ItemVenda::where('id_venda', $venda->id_venda)
            ->sum('subtotal') - $venda->desconto;

        $venda->update(['valor_total' => max(0, $novoTotal)]);

        return response()->json([
            'item'  => $item,
            'venda' => $venda->fresh(['cliente', 'usuario']),
        ], 201);
    }
}
