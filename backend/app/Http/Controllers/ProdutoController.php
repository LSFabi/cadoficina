<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdutoController extends Controller
{
    public function index()
    {
        return response()->json(Produto::with('categoria')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_categoria'   => 'required|integer|exists:categoria,id_categoria',
            'nome'           => 'required|string|max:120',
            'codigo_barras'  => 'required|string|max:50|unique:produto,codigo_barras',
            'preco_venda'    => 'required|numeric|min:0',
            'preco_custo'    => 'nullable|numeric|min:0',
            'estoque_minimo' => 'required|integer|min:0',
            'foto_url'       => 'nullable|string|max:500',
            'ativo'          => 'boolean',
        ]);

        $produto = Produto::create($validated);

        return response()->json($produto->load('categoria'), 201);
    }

    public function show(Produto $produto)
    {
        return response()->json($produto->load('categoria'));
    }

    public function update(Request $request, Produto $produto)
    {
        $validated = $request->validate([
            'id_categoria'   => 'sometimes|required|integer|exists:categoria,id_categoria',
            'nome'           => 'sometimes|required|string|max:120',
            'codigo_barras'  => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('produto', 'codigo_barras')->ignore($produto->id_produto, 'id_produto'),
            ],
            'preco_venda'    => 'sometimes|required|numeric|min:0',
            'preco_custo'    => 'nullable|numeric|min:0',
            'estoque_minimo' => 'sometimes|required|integer|min:0',
            'foto_url'       => 'nullable|string|max:500',
            'ativo'          => 'sometimes|boolean',
        ]);

        $produto->update($validated);

        return response()->json($produto->load('categoria'));
    }

    public function destroy(Produto $produto)
    {
        $produto->update(['ativo' => false]);

        return response()->json(['message' => 'Produto desativado com sucesso.']);
    }
}
