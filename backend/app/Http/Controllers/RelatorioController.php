<?php

namespace App\Http\Controllers;

use App\Models\Devolucao;
use App\Models\Financeiro;
use App\Models\Promissoria;
use App\Models\ProdutoVariacao;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    // RF_S01 — Relatório de Vendas
    public function vendas(Request $request)
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim'    => 'required|date|after_or_equal:data_inicio',
            'status'      => 'nullable|string|in:rascunho,concluida,cancelada',
        ]);

        $query = Venda::with(['cliente', 'usuario', 'itens', 'pagamentos'])
            ->whereBetween('data_venda', [$request->data_inicio, $request->data_fim . ' 23:59:59']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $vendas = $query->orderBy('data_venda', 'desc')->get();

        $resumo = [
            'total_vendas'     => $vendas->where('status', 'concluida')->count(),
            'valor_total'      => $vendas->where('status', 'concluida')->sum('valor_total'),
            'total_canceladas' => $vendas->where('status', 'cancelada')->count(),
        ];

        return response()->json([
            'periodo' => ['inicio' => $request->data_inicio, 'fim' => $request->data_fim],
            'resumo'  => $resumo,
            'vendas'  => $vendas,
        ]);
    }

    // RF_S02 — Relatório de Estoque
    public function estoque(Request $request)
    {
        $query = ProdutoVariacao::with(['produto.categoria'])
            ->where('ativo', true);

        if ($request->has('id_categoria')) {
            $query->whereHas('produto', fn($q) => $q->where('id_categoria', $request->id_categoria));
        }

        $variacoes = $query->orderBy('qtd_estoque')->get();

        $resumo = [
            'total_variacoes' => $variacoes->count(),
            'zeradas'         => $variacoes->where('qtd_estoque', '<=', 0)->count(),
            'valor_estoque'   => $variacoes->reduce(fn($carry, $v) => $carry + ($v->qtd_estoque * ($v->produto->preco_custo ?? 0)), 0),
        ];

        return response()->json([
            'resumo'   => $resumo,
            'variacoes'=> $variacoes,
        ]);
    }

    // RF_S03 — Relatório Financeiro
    public function financeiro(Request $request)
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim'    => 'required|date|after_or_equal:data_inicio',
        ]);

        $lancamentos = Financeiro::with(['venda', 'fornecedor', 'promissoria'])
            ->whereBetween(DB::raw('DATE(criado_em)'), [$request->data_inicio, $request->data_fim])
            ->orderBy('criado_em', 'desc')
            ->get();

        $resumo = [
            'total_entradas' => $lancamentos->whereIn('tipo', ['entrada'])->sum('valor'),
            'total_saidas'   => $lancamentos->whereIn('tipo', ['saida'])->sum('valor'),
            'saldo'          => $lancamentos->whereIn('tipo', ['entrada'])->sum('valor')
                              - $lancamentos->whereIn('tipo', ['saida'])->sum('valor'),
        ];

        return response()->json([
            'periodo'    => ['inicio' => $request->data_inicio, 'fim' => $request->data_fim],
            'resumo'     => $resumo,
            'lancamentos'=> $lancamentos,
        ]);
    }

    // RF_S04 — já coberto pelo DashboardController

    // RF_S05 — Relatório de Devoluções
    public function devolucoes(Request $request)
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim'    => 'required|date|after_or_equal:data_inicio',
        ]);

        $devolucoes = Devolucao::with(['venda', 'cliente', 'usuario', 'itens.variacao.produto'])
            ->whereBetween(DB::raw('DATE(data_devolucao)'), [$request->data_inicio, $request->data_fim])
            ->orderBy('data_devolucao', 'desc')
            ->get();

        $resumo = [
            'total_devolucoes' => $devolucoes->count(),
            'valor_devolvido'  => $devolucoes->sum(fn($d) => $d->itens->sum(fn($i) => $i->quantidade * $i->valor_unitario)),
        ];

        return response()->json([
            'periodo'   => ['inicio' => $request->data_inicio, 'fim' => $request->data_fim],
            'resumo'    => $resumo,
            'devolucoes'=> $devolucoes,
        ]);
    }

    // RF_S06 — Relatório de Promissórias
    public function promissorias(Request $request)
    {
        $query = Promissoria::with(['venda', 'condicional', 'origem']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('data_inicio')) {
            $query->whereDate('data_vencimento', '>=', $request->data_inicio);
        }

        if ($request->has('data_fim')) {
            $query->whereDate('data_vencimento', '<=', $request->data_fim);
        }

        $promissorias = $query->orderBy('data_vencimento')->get();

        $resumo = [
            'total'          => $promissorias->count(),
            'valor_total'    => $promissorias->sum('valor_total'),
            'pendentes'      => $promissorias->whereIn('status', ['pendente', 'carencia'])->count(),
            'valor_pendente' => $promissorias->whereIn('status', ['pendente', 'carencia'])->sum('valor_total'),
        ];

        return response()->json([
            'resumo'       => $resumo,
            'promissorias' => $promissorias,
        ]);
    }
}
