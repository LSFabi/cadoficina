<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\CondicionalController;
use App\Http\Controllers\CreditoLojaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevolucaoController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\MovEstoqueController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProdutoVariacaoController;
use App\Http\Controllers\PromissoriaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\VendaController;
use App\Http\Controllers\VendaPagamentoController;
use Illuminate\Support\Facades\Route;

// ─── Auth (pública) ────────────────────────────────────────────────────────
Route::post('auth/login', [AuthController::class, 'login']);

// ─── Rotas protegidas ──────────────────────────────────────────────────────
Route::middleware('auth.sessao')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // ── Cadastro base ──────────────────────────────────────────────────────
    Route::apiResource('clientes', ClienteController::class);

    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('fornecedores', FornecedorController::class);

    Route::apiResource('produtos', ProdutoController::class);
    Route::get('produtos/{produto}/variacoes', [ProdutoVariacaoController::class, 'index']);
    Route::post('produtos/{produto}/variacoes', [ProdutoVariacaoController::class, 'store']);
    Route::get('produtos/{produto}/variacoes/{variacao}', [ProdutoVariacaoController::class, 'show']);
    Route::put('produtos/{produto}/variacoes/{variacao}', [ProdutoVariacaoController::class, 'update']);
    Route::delete('produtos/{produto}/variacoes/{variacao}', [ProdutoVariacaoController::class, 'destroy']);

    // ── Estoque — RF_B03 / RF_F04 ──────────────────────────────────────────
    Route::get('estoque/variacoes', [MovEstoqueController::class, 'variacoes']);
    Route::get('estoque/movimentacoes', [MovEstoqueController::class, 'index']);
    Route::post('estoque/ajuste', [MovEstoqueController::class, 'ajuste']);

    // ── Configuração — RF_B06 (GET: ambos; POST/PUT: proprietária) ───────────
    Route::get('configuracao', [ConfiguracaoController::class, 'show']);

    // ── Venda — RF_F01 / RF_F01b ───────────────────────────────────────────
    Route::get('vendas', [VendaController::class, 'index']);
    Route::post('vendas', [VendaController::class, 'store']);
    Route::get('vendas/{venda}', [VendaController::class, 'show']);
    Route::post('vendas/{venda}/itens', [VendaController::class, 'addItem']);
    Route::delete('vendas/{venda}/itens/{item}', [VendaController::class, 'removeItem']);
    Route::patch('vendas/{venda}/confirmar', [VendaController::class, 'confirmar']);
    Route::patch('vendas/{venda}/cancelar', [VendaController::class, 'cancel']);
    Route::patch('vendas/{venda}/reabrir', [VendaController::class, 'reabrir']);
    Route::post('vendas/{venda}/pagamentos', [VendaPagamentoController::class, 'store']);
    Route::patch('pagamentos/{pagamento}/estornar', [VendaPagamentoController::class, 'estornar']);

    // ── Condicional — RF_F02 / RF_F02b ─────────────────────────────────────
    Route::get('condicionais', [CondicionalController::class, 'index']);
    Route::post('condicionais', [CondicionalController::class, 'store']);
    Route::get('condicionais/{condicional}', [CondicionalController::class, 'show']);
    Route::post('condicionais/{condicional}/itens', [CondicionalController::class, 'addItem']);
    Route::patch('condicionais/{condicional}/itens/{item}/devolver', [CondicionalController::class, 'devolverItem']);
    Route::patch('condicionais/{condicional}/fechar', [CondicionalController::class, 'fechar']);
    Route::patch('condicionais/{condicional}/cancelar', [CondicionalController::class, 'cancelar']);

    // ── Devolução — RF_F03 ─────────────────────────────────────────────────
    Route::post('devolucoes', [DevolucaoController::class, 'store']);
    Route::post('devolucoes/{devolucao}/itens', [DevolucaoController::class, 'addItem']);

    // ── Crédito da Loja — RF_F06 / RF_F06b ────────────────────────────────
    Route::get('creditos', [CreditoLojaController::class, 'index']);
    Route::post('creditos', [CreditoLojaController::class, 'store']);
    Route::get('creditos/{credito}', [CreditoLojaController::class, 'show']);
    Route::patch('creditos/{credito}/cancelar', [CreditoLojaController::class, 'cancelar']);
    Route::get('clientes/{id_cliente}/creditos', [CreditoLojaController::class, 'porCliente']);

    // ── Promissória — RF_F05 a RF_F05d (proprietária) ──────────────────────
    Route::middleware('auth.sessao:proprietaria')->group(function () {
        Route::get('promissorias', [PromissoriaController::class, 'index']);
        Route::post('promissorias', [PromissoriaController::class, 'store']);
        Route::get('promissorias/{promissoria}', [PromissoriaController::class, 'show']);
        Route::patch('promissorias/{promissoria}/acordo', [PromissoriaController::class, 'acordo']);
        Route::patch('promissorias/{promissoria}/juridico', [PromissoriaController::class, 'juridico']);
        Route::patch('promissorias/{promissoria}/quitar', [PromissoriaController::class, 'quitar']);
        Route::patch('promissorias/{promissoria}/cancelar', [PromissoriaController::class, 'cancelar']);
        Route::patch('promissorias/{promissoria}/documento', [PromissoriaController::class, 'atualizarDocumento']);
    });

    // ── Financeiro — RF_B04 (proprietária) ────────────────────────────────
    Route::middleware('auth.sessao:proprietaria')->group(function () {
        Route::get('financeiro', [FinanceiroController::class, 'index']);
        Route::post('financeiro', [FinanceiroController::class, 'store']);
        Route::get('financeiro/{financeiro}', [FinanceiroController::class, 'show']);
        Route::put('financeiro/{financeiro}', [FinanceiroController::class, 'update']);
        Route::delete('financeiro/{financeiro}', [FinanceiroController::class, 'destroy']);
    });

    // ── Dashboard — RF_S04 ─────────────────────────────────────────────────
    Route::get('dashboard', [DashboardController::class, 'index']);

    // ── Relatórios — RF_S02: ambos; RF_S01/S03/S05/S06: proprietária ─────────
    Route::get('relatorios/estoque', [RelatorioController::class, 'estoque']);

    Route::prefix('relatorios')->middleware('auth.sessao:proprietaria')->group(function () {
        Route::get('vendas', [RelatorioController::class, 'vendas']);
        Route::get('financeiro', [RelatorioController::class, 'financeiro']);
        Route::get('devolucoes', [RelatorioController::class, 'devolucoes']);
        Route::get('promissorias', [RelatorioController::class, 'promissorias']);
    });
});

// ─── Rotas exclusivas proprietária — middleware único (sem herança de auth.sessao) ──
Route::middleware('auth.sessao:proprietaria')->group(function () {
    Route::post('clientes/{cliente}/anonimizar', [ClienteController::class, 'anonimizar']);
    Route::post('configuracao', [ConfiguracaoController::class, 'store']);
    Route::put('configuracao', [ConfiguracaoController::class, 'update']);
});
