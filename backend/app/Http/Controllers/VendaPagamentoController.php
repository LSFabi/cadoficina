<?php

namespace App\Http\Controllers;

use App\Models\CreditoLoja;
use App\Models\Venda;
use App\Models\VendaPagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendaPagamentoController extends Controller
{
    public function store(Request $request, Venda $venda)
    {
        // O trigger trg_venda_pagamento_before_insert (RN11) valida:
        //   - venda existe e está em status 'rascunho'
        //   - valor do pagamento não excede saldo devedor
        // O trigger trg_venda_pagamento_after_insert (RN16) atualiza
        //   o status da venda para 'concluida' quando totalmente quitada.
        // Nenhuma dessas ações deve ser duplicada aqui.

        $validated = $request->validate([
            'id_credito'      => 'nullable|integer|exists:credito_loja,id_credito',
            'forma_pagamento' => 'required|string|in:dinheiro,pix,cartao_debito,cartao_credito,promissoria,credito_loja',
            'valor'           => 'required|numeric|min:0.01',
            'parcelas'        => 'nullable|integer|min:1',
            'valor_recebido'  => 'nullable|numeric|min:0',
            // troco: STORED GENERATED — calculado pelo banco, não aceitar na entrada
        ]);

        // Validação específica para pagamento via crédito da loja
        $credito = null;
        if ($validated['forma_pagamento'] === 'credito_loja') {
            if (empty($validated['id_credito'])) {
                return response()->json(['message' => 'id_credito é obrigatório para pagamento via credito_loja.'], 422);
            }

            $credito = CreditoLoja::find($validated['id_credito']);

            if (!$credito || $credito->status !== 'disponivel' || $credito->valor_saldo <= 0) {
                return response()->json(['message' => 'Crédito indisponível ou com saldo zero.'], 422);
            }

            if ($validated['valor'] > $credito->valor_saldo) {
                return response()->json([
                    'message' => 'Valor informado excede o saldo disponível do crédito (saldo: ' . $credito->valor_saldo . ').',
                ], 422);
            }
        }

        if ($venda->itens()->count() === 0) {
            return response()->json(['message' => 'Venda sem itens não pode receber pagamento.'], 422);
        }

        if ($venda->valor_total <= 0) {
            return response()->json(['message' => 'Venda com valor total zero não pode receber pagamento.'], 422);
        }

        try {
        $result = DB::transaction(function () use ($validated, $venda, $credito) {
            $pagamento = VendaPagamento::create([
                'id_venda'        => $venda->id_venda,
                'id_credito'      => $validated['id_credito'] ?? null,
                'forma_pagamento' => $validated['forma_pagamento'],
                'valor'           => $validated['valor'],
                'parcelas'        => $validated['parcelas'] ?? 1,
                'valor_recebido'  => $validated['valor_recebido'] ?? null,
                // troco: STORED GENERATED — não inserir
                // status default 'ativo' pelo banco — não inserir
            ]);

            $pagamento->refresh();

            // Consumir crédito da loja após pagamento inserido
            if ($credito) {
                $novoUtilizado = $credito->valor_utilizado + $validated['valor'];
                $credito->valor_utilizado = $novoUtilizado;
                // valor_saldo é STORED GENERATED — banco recalcula como valor_original - valor_utilizado
                // Se saldo zerar, marcar como utilizado
                $novoSaldo = $credito->valor_original - $novoUtilizado;
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

        return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno.', 'error' => $e->getMessage()], 500);
        }
    }

    public function estornar(VendaPagamento $pagamento)
    {
        if ($pagamento->status !== 'ativo') {
            return response()->json(['message' => 'Apenas pagamentos com status ativo podem ser estornados.'], 422);
        }

        $pagamento->update(['status' => 'estornado']);

        return response()->json($pagamento->fresh());
    }
}
