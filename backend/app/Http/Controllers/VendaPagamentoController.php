<?php

namespace App\Http\Controllers;

use App\Models\VendaPagamento;
use App\Models\Venda;
use Illuminate\Http\Request;

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

        return response()->json([
            'pagamento' => $pagamento,
            'venda'     => $venda->fresh(['cliente', 'usuario']),
        ], 201);
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
