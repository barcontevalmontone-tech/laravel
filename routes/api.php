<?php

use App\Http\Controllers\Api\ArticoloController;
use App\Http\Controllers\Api\MovimentoController;
use App\Http\Controllers\Api\RiordinoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/riordini', [RiordinoController::class, 'index']);

    Route::get('articoli/{articolo:codice}/movimenti', [MovimentoController::class, 'index']);
    Route::post('articoli/{articolo}/movimenti', [MovimentoController::class, 'store']);

    Route::apiResource('articoli', ArticoloController::class)
        ->parameters(['articoli' => 'articolo'])
        ->except(['destroy']);

    // Distruzione articolo: solo amministratori.
    Route::delete('articoli/{articolo}', [ArticoloController::class, 'destroy'])
        ->middleware('admin');
});
