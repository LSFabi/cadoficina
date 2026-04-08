<?php

namespace App\Http\Controllers;

use App\Models\CreditoLoja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditoLojaController extends Controller
{
    // GET /creditos?id_cliente=X
    public function index(Request $request)
    {
        $query = CreditoLoja::with(['cliente', 'usuario', 'devolucao']);

        if ($request->has('id_cliente')) {
            $query->where('id_cliente', $request->id_cliente);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('criado_em', 'desc')->get());
    }

    // GET /creditos/{credito}
    public function show(CreditoLoja $credito)
    {
        return response()->json($credito->load(['cliente', 'usuario', 'devolucao']));
    }

    // GET /clientes/{id_cliente}/creditos — RF_F06b (saldo disponível)
    public function porCliente(int $idCliente)
    {
        $creditos = CreditoLoja::where('id_cliente', $idCliente)
            ->with(['devolucao'])
            ->orderBy('criado_em', 'desc')
            ->get();

        $saldoTotal = $creditos->where('status', 'disponivel')->sum('valor_saldo');

        return response()->json([
            'id_cliente'  => $idCliente,
            'saldo_total' => $saldoTotal,
            'creditos'    => $creditos,
        ]);
    }

    // POST /creditos — RF_F06 (crédito manual)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cliente'     => 'required|integer|exists:cliente,id_cliente',
            'id_usuario'     => 'nullable|integer|exists:usuario,id_usuario',
            'valor_original' => 'required|numeric|min:0.01',
            'data_validade'  => 'nullable|date|after:today',
            'motivo'         => 'required|string',
        ]);

        // origem='manual' — data_validade pode ser null ou com prazo
        // Crédito de cancelamento_venda tem data_validade=NULL (RN15) — esse endpoint é só manual
        $credito = CreditoLoja::create([
            'id_cliente'     => $validated['id_cliente'],
            'id_usuario'     => $validated['id_usuario'] ?? null,
            'origem'         => 'manual',
            'valor_original' => $validated['valor_original'],
            'data_validade'  => $validated['data_validade'] ?? null,
            'motivo'         => $validated['motivo'],
            'status'         => 'disponivel',
        ]);

        return response()->json($credito->fresh(['cliente', 'usuario']), 201);
    }

    // PATCH /creditos/{credito}/cancelar
    public function cancelar(CreditoLoja $credito)
    {
        if ($credito->status !== 'disponivel') {
            return response()->json(['message' => 'Apenas créditos disponíveis podem ser cancelados.'], 422);
        }

        $credito->update(['status' => 'cancelado']);

        return response()->json($credito->fresh());
    }
}
