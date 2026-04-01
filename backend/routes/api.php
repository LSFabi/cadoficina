<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\VendaController;
use App\Http\Controllers\DevolucaoController;
use App\Http\Controllers\VendaPagamentoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('clientes', ClienteController::class);
Route::apiResource('categorias', CategoriaController::class);
Route::apiResource('produtos', ProdutoController::class);
Route::apiResource('fornecedores', FornecedorController::class);

// Venda — ciclo controlado
Route::get('vendas', [VendaController::class, 'index']);
Route::get('vendas/{venda}', [VendaController::class, 'show']);
Route::post('vendas', [VendaController::class, 'store']);
Route::post('vendas/{venda}/itens', [VendaController::class, 'addItem']);
Route::delete('vendas/{venda}/itens/{item}', [VendaController::class, 'removeItem']);
Route::patch('vendas/{venda}/cancelar', [VendaController::class, 'cancel']);
Route::patch('vendas/{venda}/reabrir', [VendaController::class, 'reabrir']);
Route::patch('vendas/{venda}/confirmar', [VendaController::class, 'confirmar']);
Route::post('vendas/{venda}/pagamentos', [VendaPagamentoController::class, 'store']);
Route::patch('pagamentos/{pagamento}/estornar', [VendaPagamentoController::class, 'estornar']);

// Devolução — ciclo controlado
Route::post('devolucoes', [DevolucaoController::class, 'store']);
Route::post('devolucoes/{devolucao}/itens', [DevolucaoController::class, 'addItem']);
