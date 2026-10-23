<?php

use App\Http\Controllers\Magazzino\ArticoloController;
use App\Http\Controllers\Magazzino\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('magazzino.dashboard'));

Route::prefix('magazzino')->name('magazzino.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('articoli/{articolo}/movimenti', [ArticoloController::class, 'movimenta'])->name('articoli.movimenta');
    Route::resource('articoli', ArticoloController::class)->parameters(['articoli' => 'articolo']);
});
