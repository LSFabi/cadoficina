<?php

namespace App\Http\Controllers;

use App\Models\ItemVenda;
use App\Models\Venda;
use App\Services\VendaService;
use Illuminate\Http\Request;

class VendaController extends Controller
{
    public function __construct(private VendaService $vendaService) {}

    public function index()
    {
        return response()->json(
            Venda::with(['cliente', 'usuario'])->get()
        );
    }

    public function show(Venda $venda)
    {
        return response()->json(
            Venda::completa()->find($venda->id_venda)
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

    public function confirmar(Venda $venda)
    {
        return response()->json($this->vendaService->confirmar($venda));
    }

    public function reabrir(Venda $venda)
    {
        return response()->json($this->vendaService->reabrir($venda));
    }

    public function cancel(Request $request, Venda $venda)
    {
        $validated = $request->validate([
            'motivo_cancelamento' => 'required|string',
        ]);

        return response()->json($this->vendaService->cancelar($venda, $validated['motivo_cancelamento']));
    }

    public function removeItem(Venda $venda, ItemVenda $item)
    {
        return response()->json($this->vendaService->removeItem($venda, $item));
    }

    public function addItem(Request $request, Venda $venda)
    {
        $validated = $request->validate([
            'id_variacao'    => 'required|integer|exists:produto_variacao,id_variacao',
            'quantidade'     => 'required|integer|min:1',
            'preco_unitario' => 'required|numeric|min:0',
        ]);

        return response()->json($this->vendaService->addItem($venda, $validated), 201);
    }
}
