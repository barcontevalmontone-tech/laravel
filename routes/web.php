<?php

use App\Http\Controllers\DrinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('drink', DrinkController::class)
    ->except('show');
