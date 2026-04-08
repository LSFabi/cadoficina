<?php

namespace App\Http\Controllers;

use App\Models\Venda;
use App\Models\VendaPagamento;
use App\Services\PagamentoService;
use Illuminate\Http\Request;

class VendaPagamentoController extends Controller
{
    public function __construct(private PagamentoService $pagamentoService) {}

    public function store(Request $request, Venda $venda)
    {
        // O trigger trg_venda_pagamento_before_insert (RN11) valida:
        //   - venda existe e está em status 'rascunho'
        //   - valor do pagamento não excede saldo devedor
        // O trigger trg_venda_pagamento_after_insert (RN16) atualiza
        //   o status da venda para 'concluida' quando totalmente quitada.
        $validated = $request->validate([
            'id_credito'      => 'nullable|integer|exists:credito_loja,id_credito',
            'forma_pagamento' => 'required|string|in:dinheiro,pix,cartao_debito,cartao_credito,promissoria,credito_loja',
            'valor'           => 'required|numeric|min:0.01',
            'parcelas'        => 'nullable|integer|min:1',
            'valor_recebido'  => 'nullable|numeric|min:0',
            // troco: STORED GENERATED — calculado pelo banco, não aceitar na entrada
        ]);

        return response()->json($this->pagamentoService->criar($venda, $validated), 201);
    }

    public function estornar(VendaPagamento $pagamento)
    {
        return response()->json($this->pagamentoService->estornar($pagamento));
    }
}
