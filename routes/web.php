<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\CekBookingController;
use App\Http\Controllers\Customer\GudangController as CustomerGudangController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\TemuJanjiController as CustomerTemuJanjiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\TemuJanjiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('customer.home');
Route::get('/temu-janji/ketersediaan', [CustomerTemuJanjiController::class, 'availability'])
    ->middleware('throttle:60,1')
    ->name('customer.temu-janji.availability');
Route::get('/cek-booking', [CekBookingController::class, 'index'])->name('customer.cek-booking.index');
Route::post('/cek-booking', [CekBookingController::class, 'store'])
    ->middleware('throttle:customer-booking-lookup')
    ->name('customer.cek-booking.store');
Route::get('/gudang', [CustomerGudangController::class, 'index'])->name('customer.gudang.index');
Route::get('/gudang/{group}', [CustomerGudangController::class, 'show'])->name('customer.gudang.show');
Route::get('/gudang/{group}/temu-janji', [CustomerTemuJanjiController::class, 'create'])->name('customer.temu-janji.create');
Route::post('/gudang/{group}/temu-janji', [CustomerTemuJanjiController::class, 'store'])
    ->middleware('throttle:customer-booking-submit')
    ->name('customer.temu-janji.store');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:admin-login')
            ->name('login.submit');
    });

    Route::middleware('auth')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('gudang', GudangController::class)->except(['show']);
        Route::resource('jadwal', JadwalController::class)->except(['show', 'destroy']);
        Route::post('/jadwal/{jadwal}/activate', [JadwalController::class, 'activate'])->name('jadwal.activate');
        Route::post('/jadwal/{jadwal}/deactivate', [JadwalController::class, 'deactivate'])->name('jadwal.deactivate');

        Route::get('/temu-janji', [TemuJanjiController::class, 'index'])->name('temu-janji.index');
        Route::get('/temu-janji/{temuJanji}', [TemuJanjiController::class, 'show'])->name('temu-janji.show');
        Route::post('/temu-janji/{temuJanji}/approve', [TemuJanjiController::class, 'approve'])->name('temu-janji.approve');
        Route::post('/temu-janji/{temuJanji}/reject', [TemuJanjiController::class, 'reject'])->name('temu-janji.reject');
        Route::post('/temu-janji/{temuJanji}/cancel', [TemuJanjiController::class, 'cancel'])->name('temu-janji.cancel');
        Route::post('/temu-janji/{temuJanji}/complete', [TemuJanjiController::class, 'complete'])->name('temu-janji.complete');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
