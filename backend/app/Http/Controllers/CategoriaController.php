<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index()
    {
        return response()->json(Categoria::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'      => 'required|string|max:80|unique:categoria,nome',
            'descricao' => 'nullable|string',
            'ativo'     => 'boolean',
        ]);

        $categoria = Categoria::create($validated);

        return response()->json($categoria, 201);
    }

    public function show(Categoria $categoria)
    {
        return response()->json($categoria);
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nome'      => [
                'sometimes', 'required', 'string', 'max:80',
                Rule::unique('categoria', 'nome')->ignore($categoria->id_categoria, 'id_categoria'),
            ],
            'descricao' => 'nullable|string',
            'ativo'     => 'sometimes|boolean',
        ]);

        $categoria->update($validated);

        return response()->json($categoria);
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->update(['ativo' => false]);

        return response()->json(['message' => 'Categoria desativada com sucesso.']);
    }
}
