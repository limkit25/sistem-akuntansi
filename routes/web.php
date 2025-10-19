<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KlinikController;
use Illuminate\Support\Facades\Auth; // <-- Diperlukan untuk route AJAX

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| File ini mendefinisikan semua route web untuk aplikasi Anda.
*/

// Rute untuk mengambil akun berdasarkan klinik_id (AJAX)
// Diletakkan di luar grup middleware 'auth' tapi dicek di dalamnya
Route::get('/get-accounts-by-klinik/{klinik_id?}', function ($klinik_id = null) {
    // Hanya Superadmin yang boleh akses
    if (!Auth::check() || !Auth::user()->hasRole('Superadmin')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $globalAccounts = App\Models\ChartOfAccount::whereNull('klinik_id')->orderBy('kode_akun', 'asc')->get();
    $clinicAccounts = collect();

    if ($klinik_id) {
        $clinicAccounts = App\Models\ChartOfAccount::where('klinik_id', $klinik_id)->orderBy('kode_akun', 'asc')->get();
    }

    $accounts = $globalAccounts->merge($clinicAccounts)->sortBy('kode_akun');

    return response()->json($accounts);
})->name('ajax.getAccountsByKlinik');


// Mengarahkan halaman utama (/) ke halaman login jika belum login
Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest'); // Hanya untuk user yang belum login

// GRUP UNTUK SEMUA ROUTE YANG MEWAJIBKAN LOGIN
Route::middleware('auth')->group(function () {

    // Dashboard (Bisa diakses semua role yang sudah login)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // --- GRUP KHUSUS SUPERADMIN ---
    // Hanya Superadmin yang bisa mengelola User & Klinik
    Route::middleware(['role:Superadmin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('kliniks', KlinikController::class);
    });

    // --- GRUP UNTUK SUPERADMIN & ADMIN KLINIK ---
    // Superadmin & Admin Klinik bisa mengelola COA & Laporan
    // (Data Laporan akan difilter lebih lanjut di Controller)
    Route::middleware(['role:Superadmin|Admin'])->group(function () {
        // COA (Sekarang COA bisa per klinik atau global)
        Route::resource('accounts', ChartOfAccountController::class);

        // Laporan
        Route::get('laporan/buku-besar', [LaporanController::class, 'bukuBesar'])->name('laporan.bukuBesar');
        Route::get('laporan/neraca-saldo', [LaporanController::class, 'neracaSaldo'])->name('laporan.neracaSaldo');
        Route::get('laporan/laba-rugi', [LaporanController::class, 'labaRugi'])->name('laporan.labaRugi');
        Route::get('laporan/neraca', [LaporanController::class, 'neraca'])->name('laporan.neraca');
    });

    // --- GRUP UNTUK SEMUA ROLE (SUPERADMIN, ADMIN, STAF) ---
    // Semua role bisa akses Jurnal (Data akan difilter lebih lanjut di Controller)
    Route::middleware(['role:Superadmin|Admin|Staf'])->group(function () {
        // Rute detail jurnal per klinik per periode
        Route::get('jurnals/show/{klinik}/{period}', [JurnalController::class, 'showMonthly'])->name('jurnals.showMonthly');
        // Rute resource Jurnal (index, create, store, edit, update, destroy)
        Route::resource('jurnals', JurnalController::class)->except(['show']); // Method show digantikan showMonthly
    });

    // Profile (Bisa diakses semua role yang sudah login)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Memuat route bawaan Breeze (Login, Register, Lupa Password, dll.)
require __DIR__.'/auth.php';