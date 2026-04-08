<?php

namespace App\Http\Controllers;

use App\Models\Condicional;
use App\Models\CreditoLoja;
use App\Models\Financeiro;
use App\Models\Promissoria;
use App\Models\ProdutoVariacao;
use App\Models\RecebimentoPrevisto;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // GET /dashboard — RF_S04 (8 indicadores com filtro de perfil)
    public function index(Request $request)
    {
        $usuario = $request->attributes->get('usuario');
        $isProp  = $usuario->perfil === 'proprietaria';

        $hoje  = now()->toDateString();
        $mes   = now()->month;
        $ano   = now()->year;

        // Indicador 1 — Vendas do Dia (Ambos)
        $vendasDia = Venda::where('status', 'concluida')
            ->whereDate('data_venda', $hoje)
            ->selectRaw('COUNT(*) as quantidade, COALESCE(SUM(valor_total), 0) as valor_total')
            ->first();

        // Indicador 3 — Estoque Crítico (Ambos)
        $estoqueCritico = ProdutoVariacao::where('ativo', true)
            ->selectRaw('
                SUM(CASE WHEN qtd_estoque <= 0 THEN 1 ELSE 0 END) as zerados,
                COUNT(*) as total_variacoes
            ')
            ->first();

        // Indicador 4 — Condicionais Abertos (Ambos)
        // Inclui parcial_vencido: itens pendentes com prazo expirado (gerado por evt_atualiza_condicionais)
        $condicionaisAbertos = Condicional::whereIn('status', ['retirado', 'parcial', 'parcial_vencido', 'vencido'])
            ->count();

        $resultado = [
            'indicador_1_vendas_dia'       => $vendasDia,
            'indicador_3_estoque_critico'  => $estoqueCritico,
            'indicador_4_condicionais_abertos' => $condicionaisAbertos,
        ];

        if ($isProp) {
            // Indicador 2 — Faturamento Mensal (Proprietária)
            $faturamentoMensal = Venda::where('status', 'concluida')
                ->whereMonth('data_venda', $mes)
                ->whereYear('data_venda', $ano)
                ->sum('valor_total');

            // Indicador 5 — A Receber Cartão (Proprietária)
            $aReceberCartao = RecebimentoPrevisto::where('status', 'pendente')
                ->selectRaw('ano_previsto, mes_previsto, SUM(valor_parcela) as total')
                ->groupBy('ano_previsto', 'mes_previsto')
                ->orderBy('ano_previsto')
                ->orderBy('mes_previsto')
                ->get();

            // Indicador 6 — A Receber Promissórias (Proprietária)
            $aReceberPromissorias = Promissoria::whereIn('status', ['pendente', 'carencia'])
                ->selectRaw('YEAR(data_vencimento) as ano, MONTH(data_vencimento) as mes, SUM(valor_total) as total')
                ->groupBy(DB::raw('YEAR(data_vencimento), MONTH(data_vencimento)'))
                ->orderBy('ano')
                ->orderBy('mes')
                ->get();

            // Indicador 7 — Acordos Ativos (Proprietária)
            $acordosAtivos = Promissoria::where('status', 'substituida')
                ->selectRaw('COUNT(*) as quantidade, SUM(valor_total) as valor_total')
                ->first();

            // Indicador 8 — Perdas do Mês (Proprietária)
            $perdasMes = Financeiro::where('categoria', 'perda')
                ->whereMonth('criado_em', $mes)
                ->whereYear('criado_em', $ano)
                ->sum('valor');

            $resultado += [
                'indicador_2_faturamento_mensal'    => $faturamentoMensal,
                'indicador_5_a_receber_cartao'      => $aReceberCartao,
                'indicador_6_a_receber_promissorias'=> $aReceberPromissorias,
                'indicador_7_acordos_ativos'        => $acordosAtivos,
                'indicador_8_perdas_mes'            => $perdasMes,
            ];
        }

        return response()->json($resultado);
    }
}
