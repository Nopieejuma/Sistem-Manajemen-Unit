<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\TemuJanjiController;
use Illuminate\Support\Facades\Route;

// Halaman Utama / Welcome
Route::get('/', function () {
    return view('welcome');
});

// Route Guest (Belum Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

});

// Route Auth (Sudah Login)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route Resource Gudang (Index, Create, Store, Edit, Update, Destroy)
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
