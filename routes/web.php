<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Magazzino\ArticoloController;
use App\Http\Controllers\Magazzino\DashboardController as MagazzinoDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

// Area magazzino protetta da sessione (login web).
Route::prefix('magazzino')->name('magazzino.')->middleware('auth')->group(function (): void {
    Route::get('/', [MagazzinoDashboardController::class, 'index'])->name('dashboard');

    Route::post('articoli/{articolo}/movimenti', [ArticoloController::class, 'movimenta'])->name('articoli.movimenta');

    // Modifiche distruttive riservate agli amministratori.
    Route::delete('articoli/{articolo}', [ArticoloController::class, 'destroy'])
        ->name('articoli.destroy')
        ->middleware('admin');

    Route::resource('articoli', ArticoloController::class)
        ->parameters(['articoli' => 'articolo'])
        ->except(['destroy']);
});

// Stub temporaneo: evita l'errore 500 sul redirect del middleware auth.
Route::get('/login', fn () => view('auth.login'))->name('login');

// Punto cassa POS Bar, accessibile solo ad operatori autenticati.
Route::get('/pos', fn () => view('pos.index'))
    ->middleware('auth')
    ->name('pos.index');
