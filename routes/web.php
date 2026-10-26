<?php

use App\Http\Controllers\Customer\CekBookingController;
use App\Http\Controllers\Customer\GudangController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\TemuJanjiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('customer.home');
Route::get('/temu-janji/ketersediaan', [TemuJanjiController::class, 'availability'])
    ->middleware('throttle:60,1')
    ->name('customer.temu-janji.availability');
Route::get('/cek-booking', [CekBookingController::class, 'index'])->name('customer.cek-booking.index');
Route::post('/cek-booking', [CekBookingController::class, 'store'])
    ->middleware('throttle:customer-booking-lookup')
    ->name('customer.cek-booking.store');
Route::get('/gudang', [GudangController::class, 'index'])->name('customer.gudang.index');
Route::get('/gudang/{group}', [GudangController::class, 'show'])->name('customer.gudang.show');
Route::get('/gudang/{group}/temu-janji', [TemuJanjiController::class, 'create'])->name('customer.temu-janji.create');

Route::post('/gudang/{group}/temu-janji', [TemuJanjiController::class, 'store'])->name('customer.temu-janji.store');
