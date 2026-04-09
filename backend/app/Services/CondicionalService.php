<?php

namespace App\Services;

use App\Models\Condicional;
use App\Models\ItemCondicional;
use App\Models\ItemVenda;
use App\Models\MovEstoque;
use App\Models\ProdutoVariacao;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CondicionalService
{
    // RF_F02 — Criar condicional (trigger bloqueia sem data_prevista_dev)
    public function criar(array $dados): Condicional
    {
        return DB::transaction(function () use ($dados) {
            $condicional = Condicional::create([
                'id_cliente'        => $dados['id_cliente'],
                'id_usuario'        => $dados['id_usuario'] ?? null,
                'data_prevista_dev' => $dados['data_prevista_dev'],
                'status'            => 'retirado',
            ]);

            return $condicional->fresh(['cliente', 'usuario']);
        });
    }

    // Adicionar item ao condicional e debitar estoque
    public function addItem(Condicional $condicional, array $dados): array
    {
        if (!in_array($condicional->status, ['retirado', 'parcial'])) {
            throw ValidationException::withMessages(['message' => ['Condicional não está aberto para adição de itens.']]);
        }

        $variacao = ProdutoVariacao::findOrFail($dados['id_variacao']);

        if ($variacao->qtd_estoque < $dados['qtd_retirada']) {
            throw ValidationException::withMessages(['message' => ['Estoque insuficiente para a quantidade solicitada.']]);
        }

        return DB::transaction(function () use ($condicional, $dados, $variacao) {
            $item = ItemCondicional::create([
                'id_condicional'  => $condicional->id_condicional,
                'id_variacao'     => $dados['id_variacao'],
                'qtd_retirada'    => $dados['qtd_retirada'],
                'qtd_devolvida'   => 0,
                'qtd_comprada'    => 0,
                'preco_unitario'  => $dados['preco_unitario'],
                'status_item'     => 'ativo',
            ]);

            $variacao->decrement('qtd_estoque', $dados['qtd_retirada']);

            MovEstoque::create([
                'id_variacao' => $dados['id_variacao'],
                'tipo'        => 'condicional_retirada',
                'quantidade'  => $dados['qtd_retirada'],
                'motivo'      => 'Saída para condicional #' . $condicional->id_condicional,
            ]);

            return [
                'item'        => $item->fresh(['variacao.produto']),
                'condicional' => $condicional->fresh(['cliente', 'usuario', 'itens']),
            ];
        });
    }

    // Devolução parcial de item (RN05)
    public function devolverItem(Condicional $condicional, ItemCondicional $item, int $quantidade): array
    {
        if ($item->id_condicional !== $condicional->id_condicional) {
            throw ValidationException::withMessages(['message' => ['Item não pertence a este condicional.']]);
        }

        if ($item->status_item !== 'ativo') {
            throw ValidationException::withMessages(['message' => ['Item já encerrado.']]);
        }

        $qtdDisponivel = $item->qtd_retirada - $item->qtd_devolvida - $item->qtd_comprada;

        if ($quantidade > $qtdDisponivel) {
            throw ValidationException::withMessages(['message' => ['Quantidade a devolver supera o saldo pendente.']]);
        }

        return DB::transaction(function () use ($condicional, $item, $quantidade) {
            $item->increment('qtd_devolvida', $quantidade);

            // Restaurar estoque
            ProdutoVariacao::find($item->id_variacao)->increment('qtd_estoque', $quantidade);

            MovEstoque::create([
                'id_variacao' => $item->id_variacao,
                'tipo'        => 'condicional_retorno',
                'quantidade'  => $quantidade,
                'motivo'      => 'Devolução parcial condicional #' . $condicional->id_condicional,
            ]);

            // Verificar se item foi totalmente resolvido
            $item->refresh();
            $pendente = $item->qtd_retirada - $item->qtd_devolvida - $item->qtd_comprada;
            if ($pendente === 0) {
                $item->update(['status_item' => 'devolvido']);
            }

            // Atualizar status do condicional
            $this->recalcularStatus($condicional);

            return [
                'item'        => $item->fresh(),
                'condicional' => $condicional->fresh(['cliente', 'usuario', 'itens']),
            ];
        });
    }

    // RF_F02b — Fechar condicional: converte itens pendentes em venda (RN06)
    public function fechar(Condicional $condicional): Condicional
    {
        if (!in_array($condicional->status, ['retirado', 'parcial', 'parcial_vencido', 'vencido'])) {
            throw ValidationException::withMessages(['message' => ['Condicional não pode ser fechado no status atual.']]);
        }

        return DB::transaction(function () use ($condicional) {
            $itensAtivos = $condicional->itens()->where('status_item', 'ativo')->get();

            foreach ($itensAtivos as $item) {
                $pendente = $item->qtd_retirada - $item->qtd_devolvida - $item->qtd_comprada;
                if ($pendente > 0) {
                    // Converter em venda: registrar como qtd_comprada
                    $item->update([
                        'qtd_comprada' => $item->qtd_comprada + $pendente,
                        'status_item'  => 'convertido',
                    ]);
                }
            }

            $condicional->update(['status' => 'fechado']);

            return $condicional->fresh(['cliente', 'usuario', 'itens']);
        });
    }

    // RF_F02b — Cancelar condicional
    public function cancelar(Condicional $condicional, string $tipoCancelamento): Condicional
    {
        if (!in_array($condicional->status, ['retirado', 'parcial', 'parcial_vencido', 'vencido'])) {
            throw ValidationException::withMessages(['message' => ['Condicional não pode ser cancelado no status atual.']]);
        }

        return DB::transaction(function () use ($condicional, $tipoCancelamento) {
            // Devolver itens ainda ativos ao estoque
            $itensAtivos = $condicional->itens()->where('status_item', 'ativo')->get();

            foreach ($itensAtivos as $item) {
                $pendente = $item->qtd_retirada - $item->qtd_devolvida - $item->qtd_comprada;
                if ($pendente > 0) {
                    ProdutoVariacao::find($item->id_variacao)->increment('qtd_estoque', $pendente);

                    MovEstoque::create([
                        'id_variacao' => $item->id_variacao,
                        'tipo'        => 'condicional_retorno',
                        'quantidade'  => $pendente,
                        'motivo'      => 'Cancelamento condicional #' . $condicional->id_condicional,
                    ]);

                    $item->update(['status_item' => 'perdido']);
                }
            }

            $condicional->update([
                'status'             => 'cancelado',
                'tipo_cancelamento'  => $tipoCancelamento,
            ]);

            return $condicional->fresh(['cliente', 'usuario', 'itens']);
        });
    }

    private function recalcularStatus(Condicional $condicional): void
    {
        $itens = $condicional->itens()->where('status_item', 'ativo')->get();

        if ($itens->isEmpty()) {
            $condicional->update(['status' => 'devolvido']);
            return;
        }

        $statusAtual = $condicional->fresh()->status;

        if (in_array($statusAtual, ['vencido', 'parcial_vencido'])) {
            $condicional->update(['status' => 'parcial_vencido']);
        } else {
            $condicional->update(['status' => 'parcial']);
        }
    }
}
