<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    // GET /configuracao — RF_B06 (singleton)
    public function show()
    {
        $config = Configuracao::first();

        if (!$config) {
            return response()->json(['message' => 'Configuração não encontrada.'], 404);
        }

        return response()->json($config);
    }

    // POST /configuracao — criar (apenas se não existir; trigger bloqueia 2º registro)
    public function store(Request $request)
    {
        if (Configuracao::exists()) {
            return response()->json(['message' => 'Configuração já existe. Use PUT para atualizar.'], 422);
        }

        $validated = $request->validate([
            'nome_loja' => 'required|string|max:100',
            'cnpj'      => 'nullable|string|max:18',
            'telefone'  => 'nullable|string|max:20',
            'endereco'  => 'nullable|string',
            'logo_url'  => 'nullable|string|max:500',
        ]);

        $config = Configuracao::create($validated);

        return response()->json($config, 201);
    }

    // PUT /configuracao — RF_B06 (atualizar singleton)
    public function update(Request $request)
    {
        $config = Configuracao::first();

        if (!$config) {
            return response()->json(['message' => 'Configuração não encontrada. Use POST para criar.'], 404);
        }

        $validated = $request->validate([
            'nome_loja' => 'sometimes|required|string|max:100',
            'cnpj'      => 'nullable|string|max:18',
            'telefone'  => 'nullable|string|max:20',
            'endereco'  => 'nullable|string',
            'logo_url'  => 'nullable|string|max:500',
        ]);

        $config->update($validated);

        return response()->json($config->fresh());
    }
}
