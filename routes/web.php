<?php

use App\Http\Controllers\SalaryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SalaryController::class, 'index'])->name('home');
Route::post('/calcola', [SalaryController::class, 'calcola'])->name('calcola');
