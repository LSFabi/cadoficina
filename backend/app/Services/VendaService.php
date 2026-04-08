<?php

namespace App\Services;

use App\Models\Devolucao;
use App\Models\ItemVenda;
use App\Models\MovEstoque;
use App\Models\ProdutoVariacao;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendaService
{
    public function confirmar(Venda $venda): Venda
    {
        if ($venda->status !== 'rascunho') {
            throw ValidationException::withMessages(['message' => ['Apenas vendas em status rascunho podem ser confirmadas.']]);
        }

        if ($venda->itens()->count() === 0) {
            throw ValidationException::withMessages(['message' => ['A venda não possui itens.']]);
        }

        if ($venda->valor_total <= 0) {
            throw ValidationException::withMessages(['message' => ['O valor total da venda deve ser maior que zero.']]);
        }

        if ($venda->pagamentos()->ativos()->count() > 0) {
            throw ValidationException::withMessages(['message' => ['Venda com pagamento ativo não pode ser reconfirmada.']]);
        }

        if (Devolucao::where('id_venda', $venda->id_venda)->exists()) {
            throw ValidationException::withMessages(['message' => ['Venda devolvida não pode ser reconfirmada.']]);
        }

        return DB::transaction(function () use ($venda) {
            $venda->update(['status' => 'concluida']);

            return Venda::completa()->find($venda->id_venda);
        });
    }

    public function cancelar(Venda $venda, string $motivo): Venda
    {
        if ($venda->status === 'cancelada') {
            throw ValidationException::withMessages(['message' => ['Venda já está cancelada.']]);
        }

        if (!in_array($venda->status, ['rascunho', 'concluida'])) {
            throw ValidationException::withMessages(['message' => ['Apenas vendas em status rascunho ou concluida podem ser canceladas.']]);
        }

        return DB::transaction(function () use ($venda, $motivo) {
            $venda->update([
                'status'              => 'cancelada',
                'motivo_cancelamento' => $motivo,
                'data_cancelamento'   => now(),
            ]);

            return Venda::completa()->find($venda->id_venda);
        });
    }

    public function reabrir(Venda $venda): Venda
    {
        if ($venda->status !== 'cancelada') {
            throw ValidationException::withMessages(['message' => ['Apenas vendas canceladas podem ser reabertas.']]);
        }

        if ($venda->pagamentos()->ativos()->count() > 0) {
            throw ValidationException::withMessages(['message' => ['Existem pagamentos ativos vinculados a esta venda. Estorne todos os pagamentos antes de reabrir.']]);
        }

        return DB::transaction(function () use ($venda) {
            $venda->update([
                'status'              => 'rascunho',
                'motivo_cancelamento' => null,
                'data_cancelamento'   => null,
            ]);

            return Venda::completa()->find($venda->id_venda);
        });
    }

    public function addItem(Venda $venda, array $dados): Venda
    {
        if ($venda->status !== 'rascunho') {
            throw ValidationException::withMessages(['message' => ['Itens só podem ser adicionados a vendas em status rascunho.']]);
        }

        return DB::transaction(function () use ($venda, $dados) {
            // subtotal é STORED GENERATED (quantidade × preco_unitario) — não inserir.
            // O trigger trg_item_venda_before_insert (RN02) valida estoque e variacao ativa.
            // O trigger trg_item_venda_after_insert deduz estoque e registra mov_estoque.
            ItemVenda::create([
                'id_venda'       => $venda->id_venda,
                'id_variacao'    => $dados['id_variacao'],
                'quantidade'     => $dados['quantidade'],
                'preco_unitario' => $dados['preco_unitario'],
            ]);

            $novoTotal = ItemVenda::where('id_venda', $venda->id_venda)->sum('subtotal') - $venda->desconto;
            $venda->update(['valor_total' => max(0, $novoTotal)]);

            return Venda::completa()->find($venda->id_venda);
        });
    }

    public function removeItem(Venda $venda, ItemVenda $item): Venda
    {
        if ($item->id_venda !== $venda->id_venda) {
            throw ValidationException::withMessages(['message' => ['Item não pertence a esta venda.']]);
        }

        if ($venda->status !== 'rascunho') {
            throw ValidationException::withMessages(['message' => ['Itens só podem ser removidos de vendas em status rascunho.']]);
        }

        return DB::transaction(function () use ($venda, $item) {
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
    }
}
