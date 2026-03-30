<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FornecedorController extends Controller
{
    public function index()
    {
        return response()->json(Fornecedor::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'        => 'required|string|max:120',
            'telefone'    => 'required|string|max:20',
            'email'       => 'nullable|email|max:120|unique:fornecedor,email',
            'cnpj'        => 'nullable|string|max:18|unique:fornecedor,cnpj',
            'observacoes' => 'nullable|string',
            'ativo'       => 'boolean',
        ]);

        $fornecedor = Fornecedor::create($validated);

        return response()->json($fornecedor, 201);
    }

    public function show(Fornecedor $fornecedor)
    {
        return response()->json($fornecedor);
    }

    public function update(Request $request, Fornecedor $fornecedor)
    {
        $validated = $request->validate([
            'nome'        => 'sometimes|required|string|max:120',
            'telefone'    => 'sometimes|required|string|max:20',
            'email'       => [
                'nullable', 'email', 'max:120',
                Rule::unique('fornecedor', 'email')->ignore($fornecedor->id_fornecedor, 'id_fornecedor'),
            ],
            'cnpj'        => [
                'nullable', 'string', 'max:18',
                Rule::unique('fornecedor', 'cnpj')->ignore($fornecedor->id_fornecedor, 'id_fornecedor'),
            ],
            'observacoes' => 'nullable|string',
            'ativo'       => 'sometimes|boolean',
        ]);

        $fornecedor->update($validated);

        return response()->json($fornecedor);
    }

    public function destroy(Fornecedor $fornecedor)
    {
        $fornecedor->update(['ativo' => false]);

        return response()->json(['message' => 'Fornecedor desativado com sucesso.']);
    }
}
