<?php

namespace App\Http\Controllers;

use App\Models\Promissoria;
use App\Services\PromissoriaService;
use Illuminate\Http\Request;

class PromissoriaController extends Controller
{
    public function __construct(private PromissoriaService $promissoriaService) {}

    // GET /promissorias
    public function index(Request $request)
    {
        $query = Promissoria::with(['venda', 'condicional', 'origem']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('data_vencimento')->get());
    }

    // GET /promissorias/{promissoria}
    public function show(Promissoria $promissoria)
    {
        return response()->json($promissoria->load(['venda', 'condicional', 'origem']));
    }

    // POST /promissorias — RF_F05 (emitir)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_venda'       => 'nullable|integer|exists:venda,id_venda',
            'id_condicional' => 'nullable|integer|exists:condicional,id_condicional',
            'valor_total'    => 'required|numeric|min:0.01',
            'data_vencimento'=> 'required|date|after_or_equal:today',
        ]);

        return response()->json($this->promissoriaService->emitir($validated), 201);
    }

    // PATCH /promissorias/{promissoria}/acordo — RF_F05b
    public function acordo(Request $request, Promissoria $promissoria)
    {
        $validated = $request->validate([
            'valor_total'    => 'required|numeric|min:0.01',
            'data_vencimento'=> 'required|date|after_or_equal:today',
        ]);

        return response()->json($this->promissoriaService->acordo($promissoria, $validated));
    }

    // PATCH /promissorias/{promissoria}/juridico — RF_F05c
    public function juridico(Promissoria $promissoria)
    {
        return response()->json($this->promissoriaService->encaminharJuridico($promissoria));
    }

    // PATCH /promissorias/{promissoria}/quitar — RF_F05d
    public function quitar(Request $request, Promissoria $promissoria)
    {
        $validated = $request->validate([
            'data_pagamento' => 'nullable|date',
        ]);

        return response()->json($this->promissoriaService->quitar($promissoria, $validated['data_pagamento'] ?? now()->toDateString()));
    }

    // PATCH /promissorias/{promissoria}/cancelar
    public function cancelar(Promissoria $promissoria)
    {
        return response()->json($this->promissoriaService->cancelar($promissoria));
    }

    // PATCH /promissorias/{promissoria}/documento
    public function atualizarDocumento(Request $request, Promissoria $promissoria)
    {
        $validated = $request->validate([
            'status_documento' => 'required|string|in:gerado,impresso,assinado,digitalizado',
            'url_documento'    => 'nullable|string|max:500',
        ]);

        $promissoria->update($validated);

        return response()->json($promissoria->fresh());
    }
}
