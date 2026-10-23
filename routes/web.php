<?php

use App\Http\Controllers\Magazzino\ArticoloController;
use App\Http\Controllers\Magazzino\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('magazzino.dashboard'));

// Area magazzino protetta da sessione (login web).
Route::prefix('magazzino')->name('magazzino.')->middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('articoli/{articolo}/movimenti', [ArticoloController::class, 'movimenta'])->name('articoli.movimenta');

    // Modifiche distruttive riservate agli amministratori.
    Route::delete('articoli/{articolo}', [ArticoloController::class, 'destroy'])
        ->name('articoli.destroy')
        ->middleware('admin');

    Route::resource('articoli', ArticoloController::class)
        ->parameters(['articoli' => 'articolo'])
        ->except(['destroy']);
});
