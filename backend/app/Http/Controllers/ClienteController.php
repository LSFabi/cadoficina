<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        return response()->json(Cliente::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'               => 'required|string|max:120',
            'cpf'                => 'nullable|string|max:14|unique:cliente,cpf',
            'telefone'           => 'required|string|max:20',
            'email'              => 'nullable|email|max:120|unique:cliente,email',
            'data_nascimento'    => 'nullable|date',
            'endereco'           => 'nullable|string',
            'consentimento_lgpd' => 'required|accepted',
            'data_consentimento' => 'nullable|date',
        ]);

        $cliente = Cliente::create($validated);

        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nome'               => 'sometimes|required|string|max:120',
            'cpf'                => [
                'nullable', 'string', 'max:14',
                Rule::unique('cliente', 'cpf')->ignore($cliente->id_cliente, 'id_cliente'),
            ],
            'telefone'           => 'sometimes|required|string|max:20',
            'email'              => [
                'nullable', 'email', 'max:120',
                Rule::unique('cliente', 'email')->ignore($cliente->id_cliente, 'id_cliente'),
            ],
            'data_nascimento'    => 'nullable|date',
            'endereco'           => 'nullable|string',
            'consentimento_lgpd' => 'sometimes|accepted',
            'data_consentimento' => 'nullable|date',
        ]);

        $cliente->update($validated);

        return response()->json($cliente);
    }

    public function destroy(Cliente $cliente)
    {
        return response()->json([
            'message' => 'A exclusão física de clientes não é permitida.',
            'motivo'  => 'Dados pessoais são protegidos pela LGPD e devem ser anonimizados, não removidos.',
            'acao'    => 'Use POST /clientes/{id}/anonimizar para anonimizar os dados (perfil proprietária).',
        ], 422);
    }

    // RN23 — Anonimização irreversível via procedure (perfil proprietária)
    public function anonimizar(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|integer|exists:usuario,id_usuario',
        ]);

        try {
            DB::statement('CALL proc_anonimizar_cliente(?, ?)', [
                $cliente->id_cliente,
                $validated['id_usuario'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao anonimizar cliente.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message'    => 'Cliente anonimizado com sucesso.',
            'id_cliente' => $cliente->id_cliente,
        ]);
    }
}
