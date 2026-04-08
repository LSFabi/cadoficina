<?php

namespace App\Http\Controllers;

use App\Models\Financeiro;
use Illuminate\Http\Request;

class FinanceiroController extends Controller
{
    // GET /financeiro?tipo=X&categoria=Y&data_inicio=X&data_fim=Y
    public function index(Request $request)
    {
        $query = Financeiro::with(['venda', 'fornecedor', 'promissoria']);

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->has('data_inicio')) {
            $query->whereDate('criado_em', '>=', $request->data_inicio);
        }

        if ($request->has('data_fim')) {
            $query->whereDate('criado_em', '<=', $request->data_fim);
        }

        return response()->json($query->orderBy('criado_em', 'desc')->get());
    }

    // GET /financeiro/{financeiro}
    public function show(Financeiro $financeiro)
    {
        return response()->json($financeiro->load(['venda', 'fornecedor', 'promissoria']));
    }

    // POST /financeiro — RF_B04 (lançamento manual: despesas, compras, perdas)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_venda'       => 'nullable|integer|exists:venda,id_venda',
            'id_fornecedor'  => 'nullable|integer|exists:fornecedor,id_fornecedor',
            'id_promissoria' => 'nullable|integer|exists:promissoria,id_promissoria',
            'tipo'           => 'required|string|in:entrada,saida,a_receber,a_pagar,promissoria,estorno',
            'categoria'      => 'required|string|in:venda,compra,despesa,estorno,perda,outros',
            'valor'          => 'required|numeric|min:0.01',
            'data_vencimento'=> 'nullable|date',
            'data_pagamento' => 'nullable|date',
            'descricao'      => 'nullable|string',
            'nome_fornecedor'=> 'nullable|string|max:120',
        ]);

        $financeiro = Financeiro::create($validated);

        return response()->json($financeiro->fresh(['venda', 'fornecedor', 'promissoria']), 201);
    }

    // PUT /financeiro/{financeiro}
    public function update(Request $request, Financeiro $financeiro)
    {
        $validated = $request->validate([
            'tipo'           => 'sometimes|required|string|in:entrada,saida,a_receber,a_pagar,promissoria,estorno',
            'categoria'      => 'sometimes|required|string|in:venda,compra,despesa,estorno,perda,outros',
            'valor'          => 'sometimes|required|numeric|min:0.01',
            'data_vencimento'=> 'nullable|date',
            'data_pagamento' => 'nullable|date',
            'descricao'      => 'nullable|string',
            'nome_fornecedor'=> 'nullable|string|max:120',
        ]);

        $financeiro->update($validated);

        return response()->json($financeiro->fresh(['venda', 'fornecedor', 'promissoria']));
    }

    // DELETE /financeiro/{financeiro}
    public function destroy(Financeiro $financeiro)
    {
        $financeiro->delete();

        return response()->json(['message' => 'Lançamento removido com sucesso.']);
    }
}
