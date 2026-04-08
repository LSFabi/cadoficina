<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\ProdutoVariacao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdutoVariacaoController extends Controller
{
    // GET /produtos/{produto}/variacoes
    public function index(Produto $produto)
    {
        return response()->json(
            $produto->variacoes()->where('ativo', true)->get()
        );
    }

    // POST /produtos/{produto}/variacoes
    public function store(Request $request, Produto $produto)
    {
        $validated = $request->validate([
            'cor'                   => 'required|string|max:50',
            'tamanho'               => 'required|string|max:20',
            'qtd_estoque'       => 'required|integer|min:0',
            'codigo_barras_var' => 'nullable|string|max:50|unique:produto_variacao,codigo_barras_var',
        ]);

        $variacao = ProdutoVariacao::create(array_merge($validated, [
            'id_produto' => $produto->id_produto,
        ]));

        return response()->json($variacao, 201);
    }

    // GET /produtos/{produto}/variacoes/{variacao}
    public function show(Produto $produto, ProdutoVariacao $variacao)
    {
        if ($variacao->id_produto !== $produto->id_produto) {
            return response()->json(['message' => 'Variação não pertence a este produto.'], 404);
        }

        return response()->json($variacao);
    }

    // PUT /produtos/{produto}/variacoes/{variacao}
    public function update(Request $request, Produto $produto, ProdutoVariacao $variacao)
    {
        if ($variacao->id_produto !== $produto->id_produto) {
            return response()->json(['message' => 'Variação não pertence a este produto.'], 404);
        }

        // qtd_estoque não é aceito aqui — alteração de estoque apenas via POST /estoque/ajuste
        $validated = $request->validate([
            'cor'               => 'sometimes|required|string|max:50',
            'tamanho'           => 'sometimes|required|string|max:20',
            'codigo_barras_var' => [
                'nullable', 'string', 'max:50',
                Rule::unique('produto_variacao', 'codigo_barras_var')
                    ->ignore($variacao->id_variacao, 'id_variacao'),
            ],
            'ativo' => 'sometimes|boolean',
        ]);

        $variacao->update($validated);

        return response()->json($variacao);
    }

    // DELETE /produtos/{produto}/variacoes/{variacao} — soft delete (RN08)
    public function destroy(Produto $produto, ProdutoVariacao $variacao)
    {
        if ($variacao->id_produto !== $produto->id_produto) {
            return response()->json(['message' => 'Variação não pertence a este produto.'], 404);
        }

        $variacao->update(['ativo' => false]);

        return response()->json(['message' => 'Variação desativada com sucesso.']);
    }
}
