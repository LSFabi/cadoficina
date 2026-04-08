<?php

namespace App\Http\Controllers;

use App\Models\Condicional;
use App\Models\ItemCondicional;
use App\Models\MovEstoque;
use App\Models\ProdutoVariacao;
use App\Services\CondicionalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CondicionalController extends Controller
{
    public function __construct(private CondicionalService $condicionalService) {}

    // GET /condicionais
    public function index(Request $request)
    {
        $query = Condicional::with(['cliente', 'usuario', 'itens']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('criado_em', 'desc')->get());
    }

    // GET /condicionais/{condicional}
    public function show(Condicional $condicional)
    {
        return response()->json(
            $condicional->load(['cliente', 'usuario', 'itens.variacao.produto'])
        );
    }

    // POST /condicionais — RF_F02
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cliente'        => 'required|integer|exists:cliente,id_cliente',
            'id_usuario'        => 'nullable|integer|exists:usuario,id_usuario',
            'data_prevista_dev' => 'required|date|after:today',
        ]);

        return response()->json($this->condicionalService->criar($validated), 201);
    }

    // POST /condicionais/{condicional}/itens
    public function addItem(Request $request, Condicional $condicional)
    {
        $validated = $request->validate([
            'id_variacao'    => 'required|integer|exists:produto_variacao,id_variacao',
            'qtd_retirada'   => 'required|integer|min:1',
            'preco_unitario' => 'required|numeric|min:0',
        ]);

        return response()->json($this->condicionalService->addItem($condicional, $validated), 201);
    }

    // PATCH /condicionais/{condicional}/itens/{item}/devolver
    public function devolverItem(Request $request, Condicional $condicional, ItemCondicional $item)
    {
        $validated = $request->validate([
            'quantidade' => 'required|integer|min:1',
        ]);

        return response()->json($this->condicionalService->devolverItem($condicional, $item, $validated['quantidade']));
    }

    // PATCH /condicionais/{condicional}/fechar — RF_F02b
    public function fechar(Condicional $condicional)
    {
        return response()->json($this->condicionalService->fechar($condicional));
    }

    // PATCH /condicionais/{condicional}/cancelar — RF_F02b
    public function cancelar(Request $request, Condicional $condicional)
    {
        $validated = $request->validate([
            'tipo_cancelamento' => 'required|string|in:virou_promissoria,perda,devolvido_informalmente',
        ]);

        return response()->json($this->condicionalService->cancelar($condicional, $validated['tipo_cancelamento']));
    }
}
