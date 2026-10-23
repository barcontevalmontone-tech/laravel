<?php

use App\Http\Controllers\Api\ArticoloController;
use App\Http\Controllers\Api\MovimentoController;
use App\Http\Controllers\Api\RiordinoController;
use Illuminate\Support\Facades\Route;

Route::get('/riordini', [RiordinoController::class, 'index']);

Route::apiResource('articoli', ArticoloController::class)->parameters(['articoli' => 'articolo']);

Route::get('articoli/{articolo:codice}/movimenti', [MovimentoController::class, 'index']);
Route::post('articoli/{articolo}/movimenti', [MovimentoController::class, 'store']);
