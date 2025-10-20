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
// Pastikan parameter {context} bersifat opsional (?) jika belum
Route::get('/get-accounts-for-report/{context?}', function ($context = null) { // Tambah ? dan default null
    // Cek otorisasi Superadmin
    if (!Auth::check() || !Auth::user()->hasRole('Superadmin')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $accounts = collect(); // Inisialisasi collection kosong

    // --- LOGIKA UTAMA ---
    if ($context === 'global') {
        // Jika konteks adalah 'global', ambil HANYA akun global
        $accounts = App\Models\ChartOfAccount::whereNull('klinik_id')
                        ->orderBy('kode_akun', 'asc')
                        ->get();
    } elseif (is_numeric($context)) {
        // Jika konteks adalah ID klinik (angka)
        $klinikId = (int)$context;
        // Ambil akun global DAN akun spesifik klinik itu
        $accounts = App\Models\ChartOfAccount::where(function($query) use ($klinikId) {
                            $query->whereNull('klinik_id')
                                  ->orWhere('klinik_id', $klinikId);
                        })
                        ->orderByRaw('klinik_id IS NULL DESC, kode_akun ASC')
                        ->get();
    }
    // Jika $context adalah null atau tidak valid, $accounts akan tetap kosong (ini benar)

    // Kembalikan hasil sebagai JSON (bahkan jika kosong)
    return response()->json($accounts);

})->name('ajax.getAccountsForReport'); // Sesuaikan nama jika perlu


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
        Route::get('laporan/buku-besar/export', [LaporanController::class, 'exportBukuBesar'])->name('laporan.bukuBesar.export');
        Route::get('laporan/neraca-saldo/export', [LaporanController::class, 'exportNeracaSaldo'])->name('laporan.neracaSaldo.export');
        Route::get('laporan/laba-rugi/export', [LaporanController::class, 'exportLabaRugi'])->name('laporan.labaRugi.export');
        Route::get('laporan/neraca/export', [LaporanController::class, 'exportNeraca'])->name('laporan.neraca.export');
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