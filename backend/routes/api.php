<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::apiResource('clientes', ClienteController::class);
Route::apiResource('categorias', CategoriaController::class);
