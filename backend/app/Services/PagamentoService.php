<?php

namespace App\Services;

use App\Models\CreditoLoja;
use App\Models\Venda;
use App\Models\VendaPagamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PagamentoService
{
    public function criar(Venda $venda, array $dados): array
    {
        if ($venda->status !== 'rascunho') {
            throw ValidationException::withMessages(['message' => ['Apenas vendas em status rascunho podem receber pagamento.']]);
        }

        if ($venda->itens()->count() === 0) {
            throw ValidationException::withMessages(['message' => ['Venda sem itens não pode receber pagamento.']]);
        }

        if ($venda->valor_total <= 0) {
            throw ValidationException::withMessages(['message' => ['Venda com valor total zero não pode receber pagamento.']]);
        }

        // Validação e busca do crédito antes de abrir transaction
        $credito = null;
        if ($dados['forma_pagamento'] === 'credito_loja') {
            if (empty($dados['id_credito'])) {
                throw ValidationException::withMessages(['message' => ['id_credito é obrigatório para pagamento via credito_loja.']]);
            }

            $credito = CreditoLoja::find($dados['id_credito']);

            if (!$credito || $credito->status !== 'disponivel' || $credito->valor_saldo <= 0) {
                throw ValidationException::withMessages(['message' => ['Crédito indisponível ou com saldo zero.']]);
            }

            if ($dados['valor'] > $credito->valor_saldo) {
                throw ValidationException::withMessages(['message' => ['Valor informado excede o saldo disponível do crédito (saldo: ' . $credito->valor_saldo . ').']]);
            }
        }

        return DB::transaction(function () use ($venda, $dados, $credito) {
            // troco: STORED GENERATED — não inserir
            // status default 'ativo' pelo banco — não inserir
            // Trigger RN11 valida status rascunho e saldo; RN16 marca venda como concluida
            $pagamento = VendaPagamento::create([
                'id_venda'        => $venda->id_venda,
                'id_credito'      => $dados['id_credito'] ?? null,
                'forma_pagamento' => $dados['forma_pagamento'],
                'valor'           => $dados['valor'],
                'parcelas'        => $dados['parcelas'] ?? 1,
                'valor_recebido'  => $dados['valor_recebido'] ?? null,
            ]);

            $pagamento->refresh();

            if ($credito) {
                $novoUtilizado = $credito->valor_utilizado + $dados['valor'];
                $novoSaldo     = $credito->valor_original - $novoUtilizado;
                $credito->valor_utilizado = $novoUtilizado;
                $credito->status = $novoSaldo <= 0 ? 'utilizado' : 'disponivel';
                $credito->save();
                $credito->refresh();
            }

            return [
                'pagamento' => $pagamento,
                'venda'     => $venda->fresh(['cliente', 'usuario']),
                'credito'   => $credito,
            ];
        });
    }

    public function estornar(VendaPagamento $pagamento): VendaPagamento
    {
        if ($pagamento->status !== 'ativo') {
            throw ValidationException::withMessages(['message' => ['Apenas pagamentos com status ativo podem ser estornados.']]);
        }

        // Buscar crédito fora da transaction — leitura segura antes de bloquear
        $credito = null;
        if ($pagamento->forma_pagamento === 'credito_loja' && $pagamento->id_credito) {
            $credito = CreditoLoja::find($pagamento->id_credito);
        }

        return DB::transaction(function () use ($pagamento, $credito) {
            $pagamento->update(['status' => 'estornado']);

            if ($credito) {
                $credito->valor_utilizado = max(0, $credito->valor_utilizado - $pagamento->valor);
                $credito->status = 'disponivel';
                $credito->save();
            }

            return $pagamento->fresh();
        });
    }
}
