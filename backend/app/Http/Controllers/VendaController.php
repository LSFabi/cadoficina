<?php

namespace App\Http\Controllers;

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
}
