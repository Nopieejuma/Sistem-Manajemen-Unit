<?php

use App\Http\Controllers\Customer\GudangController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\TemuJanjiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('customer.home');
Route::get('/gudang', [GudangController::class, 'index'])->name('customer.gudang.index');
Route::get('/gudang/{group}', [GudangController::class, 'show'])->name('customer.gudang.show');
Route::get('/gudang/{group}/temu-janji', [TemuJanjiController::class, 'create'])->name('customer.temu-janji.create');

Route::post('/gudang/{group}/temu-janji', [TemuJanjiController::class, 'store'])->name('customer.temu-janji.store');
