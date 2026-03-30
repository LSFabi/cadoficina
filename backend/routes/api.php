<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\VendaController;
use Illuminate\Support\Facades\Route;

Route::apiResource('clientes', ClienteController::class);
Route::apiResource('categorias', CategoriaController::class);
Route::apiResource('produtos', ProdutoController::class);
Route::apiResource('fornecedores', FornecedorController::class);

// Venda — ciclo controlado (update e destroy serão adicionados em fases posteriores)
Route::get('vendas', [VendaController::class, 'index']);
Route::get('vendas/{venda}', [VendaController::class, 'show']);
Route::post('vendas', [VendaController::class, 'store']);
