<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProfileController; // <-- Pastikan ini ada (dari Breeze)

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sinilah route yang "wajib login" akan diletakkan.
|
*/

// BUNGKUS SEMUA ROUTE KITA DI SINI
Route::middleware('auth')->group(function () {
    
    // Dashboard
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // COA
    Route::resource('accounts', ChartOfAccountController::class);

    // Jurnal
    Route::get('jurnals/detail/{period}', [JurnalController::class, 'showMonthly'])->name('jurnals.showMonthly');
    Route::resource('jurnals', JurnalController::class);

    // Laporan
    Route::get('laporan/buku-besar', [LaporanController::class, 'bukuBesar'])->name('laporan.bukuBesar');
    Route::get('laporan/neraca-saldo', [LaporanController::class, 'neracaSaldo'])->name('laporan.neracaSaldo');
    Route::get('laporan/laba-rugi', [LaporanController::class, 'labaRugi'])->name('laporan.labaRugi');
    Route::get('laporan/neraca', [LaporanController::class, 'neraca'])->name('laporan.neraca');

    // Route Profile (bawaan Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Ini adalah route untuk Login, Register, Lupa Password, dll.
// Biarkan file ini di LUAR middleware group.
require __DIR__.'/auth.php';