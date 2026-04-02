<?php

namespace App\Http\Controllers;

use App\Models\ItemVenda;
use App\Models\MovEstoque;
use App\Models\ProdutoVariacao;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            Venda::completa()->find($venda->id_venda)
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

    public function confirmar(Venda $venda)
    {
        if ($venda->status !== 'rascunho') {
            return response()->json(['message' => 'Apenas vendas em status rascunho podem ser confirmadas.'], 422);
        }

        if ($venda->itens()->count() === 0) {
            return response()->json(['message' => 'A venda não possui itens.'], 422);
        }

        if ($venda->valor_total <= 0) {
            return response()->json(['message' => 'O valor total da venda deve ser maior que zero.'], 422);
        }

        if ($venda->pagamentos()->where('status', 'ativo')->count() > 0) {
            return response()->json(['message' => 'Venda com pagamento ativo não pode ser reconfirmada.'], 422);
        }

        $venda->update(['status' => 'concluida']);

        return response()->json(Venda::completa()->find($venda->id_venda));
    }

    public function reabrir(Venda $venda)
    {
        if ($venda->status !== 'cancelada') {
            return response()->json(['message' => 'Apenas vendas canceladas podem ser reabertas.'], 422);
        }

        $pagamentosAtivos = $venda->pagamentos()->where('status', 'ativo')->count();

        if ($pagamentosAtivos > 0) {
            return response()->json([
                'message' => 'Existem pagamentos ativos vinculados a esta venda. Estorne todos os pagamentos antes de reabrir.',
            ], 422);
        }

        $venda->update([
            'status'              => 'rascunho',
            'motivo_cancelamento' => null,
            'data_cancelamento'   => null,
        ]);

        return response()->json(Venda::completa()->find($venda->id_venda));
    }

    public function cancel(Request $request, Venda $venda)
    {
        if ($venda->status === 'cancelada') {
            return response()->json(['message' => 'Venda já está cancelada.'], 422);
        }

        if (!in_array($venda->status, ['rascunho', 'concluida'])) {
            return response()->json([
                'message' => 'Apenas vendas em status rascunho ou concluida podem ser canceladas.',
            ], 422);
        }

        $validated = $request->validate([
            'motivo_cancelamento' => 'required|string',
        ]);

        $venda->update([
            'status'              => 'cancelada',
            'motivo_cancelamento' => $validated['motivo_cancelamento'],
            'data_cancelamento'   => now(),
        ]);

        return response()->json(Venda::completa()->find($venda->id_venda));
    }

    public function removeItem(Venda $venda, ItemVenda $item)
    {
        if ($item->id_venda !== $venda->id_venda) {
            return response()->json(['message' => 'Item não pertence a esta venda.'], 422);
        }

        if ($venda->status !== 'rascunho') {
            return response()->json(['message' => 'Itens só podem ser removidos de vendas em status rascunho.'], 422);
        }

        try {
        $result = DB::transaction(function () use ($venda, $item) {
            $variacao = ProdutoVariacao::find($item->id_variacao);
            $variacao->increment('qtd_estoque', $item->quantidade);

            MovEstoque::create([
                'id_variacao' => $item->id_variacao,
                'tipo'        => 'entrada',
                'quantidade'  => $item->quantidade,
                'motivo'      => 'Remocao item venda',
            ]);

            $item->delete();

            $novoTotal = ItemVenda::where('id_venda', $venda->id_venda)->sum('subtotal') - $venda->desconto;
            $venda->update(['valor_total' => max(0, $novoTotal)]);

            return Venda::completa()->find($venda->id_venda);
        });

        return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno.', 'error' => $e->getMessage()], 500);
        }
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
