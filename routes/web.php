<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\JurnalController;

Route::get('/', function () {
    return view('dashboard'); // Arahkan ke file view 'dashboard.blade.php'
})->name('dashboard'); // Kita beri nama 'dashboard'

// Route untuk Bagan Akun (COA)
Route::resource('accounts', ChartOfAccountController::class);

// TAMBAHKAN ROUTE KHUSUS INI
// 'period' akan berisi 'Tahun-Bulan', contoh: '2025-10'
Route::get('jurnals/detail/{period}', [JurnalController::class, 'showMonthly'])->name('jurnals.showMonthly');

// Route untuk Jurnal Umum
Route::resource('jurnals', JurnalController::class);