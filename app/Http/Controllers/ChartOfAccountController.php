<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Klinik; // <-- Tambahkan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- Tambahkan
use Illuminate\Validation\Rule; // <-- Tambahkan

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of the resource, filtered by clinic for non-Superadmins.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $kliniks = collect(); // Untuk filter Superadmin
        $selectedKlinikId = null;

        $accountsQuery = ChartOfAccount::with('klinik')->orderBy('klinik_id')->orderBy('kode_akun', 'asc');

        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik')->get();
            $selectedKlinikId = $request->input('klinik_id');
            if ($selectedKlinikId && $selectedKlinikId !== 'global') {
                // Filter berdasarkan klinik yang dipilih Superadmin
                $accountsQuery->where('klinik_id', $selectedKlinikId);
            } elseif ($selectedKlinikId === 'global') {
                 // Filter akun global (klinik_id = NULL)
                $accountsQuery->whereNull('klinik_id');
            }
             // Jika Superadmin tidak filter, tampilkan semua
        } else {
            // Admin Klinik hanya melihat akun kliniknya ATAU akun global
            $selectedKlinikId = $user->klinik_id;
            if ($selectedKlinikId) {
                $accountsQuery->where(function($query) use ($selectedKlinikId) {
                    $query->where('klinik_id', $selectedKlinikId)
                          ->orWhereNull('klinik_id'); // Tampilkan juga akun global
                });
            } else {
                // Admin Klinik tanpa klinik_id hanya lihat akun global
                $accountsQuery->whereNull('klinik_id');
            }
        }

        $accounts = $accountsQuery->get();

        return view('accounts.index', compact('accounts', 'kliniks', 'selectedKlinikId'));
    }

    /**
     * Show the form for creating a new resource.
     * Send clinic list for Superadmin.
     */
    public function create()
    {
        $user = Auth::user();
        $kliniks = collect();
        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik')->get();
        }
        // Admin Klinik tidak perlu memilih klinik, akan otomatis di set

        return view('accounts.create', compact('kliniks'));
    }

    /**
     * Store a newly created resource in storage.
     * Set clinic_id based on role.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $klinikId = null;

        if ($user->hasRole('Superadmin')) {
             // Superadmin bisa memilih klinik atau membuatnya global (null)
            $klinikId = $request->input('klinik_id') ?: null; // Jika kosong, set null
        } else {
             // Admin Klinik otomatis pakai kliniknya
            $klinikId = $user->klinik_id;
            if (!$klinikId) {
                 return back()->withInput()->with('error', 'Anda belum terhubung ke klinik untuk membuat akun.');
            }
        }

        $request->validate([
            'klinik_id' => $user->hasRole('Superadmin') ? ['nullable', 'integer', Rule::exists('kliniks', 'id')] : 'nullable', // Validasi hanya untuk Superadmin
            'kode_akun' => [
                'required', 'string', 'max:20',
                // Rule unique: kode_akun unik per klinik_id
                Rule::unique('chart_of_accounts')->where(function ($query) use ($klinikId) {
                    return $query->where('klinik_id', $klinikId);
                }),
            ],
            'nama_akun' => 'required|string|max:100',
            'tipe_akun' => 'required|in:Aset,Liabilitas,Ekuitas,Pendapatan,Biaya',
            'saldo_normal' => 'required|in:Debit,Kredit',
        ]);

        ChartOfAccount::create([
            'klinik_id' => $klinikId, // Simpan klinik ID
            'kode_akun' => $request->kode_akun,
            'nama_akun' => $request->nama_akun,
            'tipe_akun' => $request->tipe_akun,
            'saldo_normal' => $request->saldo_normal,
        ]);

        $redirectParams = $user->hasRole('Superadmin') ? ['klinik_id' => $klinikId] : [];
        return redirect()->route('accounts.index', $redirectParams)
                         ->with('success', 'Akun baru berhasil ditambahkan.');
    }

    /**
     * Display the specified resource. (Not used)
     */
    public function show(ChartOfAccount $account)
    {
        return redirect()->route('accounts.index');
    }

    /**
     * Show the form for editing the specified resource.
     * Includes authorization check.
     */
    public function edit(ChartOfAccount $account)
    {
        $user = Auth::user();

        // Autorisasi: Superadmin bisa edit semua, Admin Klinik hanya akun kliniknya atau global
        if (!$user->hasRole('Superadmin') && $account->klinik_id !== null && $account->klinik_id != $user->klinik_id) {
             abort(403, 'Anda tidak diizinkan mengedit akun klinik lain.');
        }

        $kliniks = collect();
        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik')->get();
        }

        return view('accounts.edit', compact('account', 'kliniks'));
    }

    /**
     * Update the specified resource in storage.
     * Includes authorization check.
     */
    public function update(Request $request, ChartOfAccount $account)
    {
        $user = Auth::user();

        // Autorisasi
        if (!$user->hasRole('Superadmin') && $account->klinik_id !== null && $account->klinik_id != $user->klinik_id) {
             abort(403, 'Anda tidak diizinkan mengupdate akun klinik lain.');
        }

        // Admin Klinik tidak boleh mengubah akun global atau memindahkan akun ke/dari global/klinik lain
        if (!$user->hasRole('Superadmin') && $account->klinik_id === null) {
            return back()->with('error', 'Anda tidak dapat mengedit akun global.');
        }

        $klinikId = $account->klinik_id; // Defaultnya tetap
        if ($user->hasRole('Superadmin')) {
             // Hanya Superadmin yang bisa mengubah klinik_id
            $klinikId = $request->input('klinik_id') ?: null;
        }

        $request->validate([
            'klinik_id' => $user->hasRole('Superadmin') ? ['nullable', 'integer', Rule::exists('kliniks', 'id')] : 'nullable',
            'kode_akun' => [
                'required', 'string', 'max:20',
                // Rule unique: abaikan baris saat ini
                Rule::unique('chart_of_accounts')->where(function ($query) use ($klinikId) {
                    return $query->where('klinik_id', $klinikId);
                })->ignore($account->id),
            ],
            'nama_akun' => 'required|string|max:100',
            'tipe_akun' => 'required|in:Aset,Liabilitas,Ekuitas,Pendapatan,Biaya',
            'saldo_normal' => 'required|in:Debit,Kredit',
        ]);

        $account->update([
            'klinik_id' => $klinikId,
            'kode_akun' => $request->kode_akun,
            'nama_akun' => $request->nama_akun,
            'tipe_akun' => $request->tipe_akun,
            'saldo_normal' => $request->saldo_normal,
        ]);

        $redirectParams = $user->hasRole('Superadmin') ? ['klinik_id' => $klinikId] : [];
        return redirect()->route('accounts.index', $redirectParams)
                         ->with('success', 'Akun berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     * Includes authorization check.
     */
    public function destroy(ChartOfAccount $account)
    {
        $user = Auth::user();

        // Autorisasi
        if (!$user->hasRole('Superadmin') && $account->klinik_id !== null && $account->klinik_id != $user->klinik_id) {
             abort(403, 'Anda tidak diizinkan menghapus akun klinik lain.');
        }
         // Admin Klinik tidak boleh menghapus akun global
        if (!$user->hasRole('Superadmin') && $account->klinik_id === null) {
            return back()->with('error', 'Anda tidak dapat menghapus akun global.');
        }

        try {
            // Cek apakah akun sudah dipakai di Jurnal Details
             if ($account->jurnalDetails()->exists()) { // Kita perlu definisikan relasi ini
                 return back()->with('error', 'Gagal menghapus akun. Akun ini sudah digunakan dalam transaksi jurnal.');
             }

            $klinikIdForRedirect = $account->klinik_id; // Simpan untuk redirect
            $account->delete();

            $redirectParams = $user->hasRole('Superadmin') ? ['klinik_id' => $klinikIdForRedirect] : [];
            return redirect()->route('accounts.index', $redirectParams)
                             ->with('success', 'Akun berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Gagal hapus akun: '.$e->getMessage());
            return back()->with('error', 'Gagal menghapus akun. Terjadi kesalahan.');
        }
    }
}