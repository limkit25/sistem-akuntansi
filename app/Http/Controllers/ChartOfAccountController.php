<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount; // 1. Panggil Model-nya
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 2. Ambil semua data dari database
        $accounts = ChartOfAccount::orderBy('kode_akun', 'asc')->get();

        // 3. Tampilkan halaman index dan kirim datanya
        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // 4. Langsung tampilkan halaman formulir create
        return view('accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 5. Validasi data yang masuk
        $request->validate([
            'kode_akun' => 'required|string|max:20|unique:chart_of_accounts',
            'nama_akun' => 'required|string|max:100',
            'tipe_akun' => 'required|in:Aset,Liabilitas,Ekuitas,Pendapatan,Biaya',
            'saldo_normal' => 'required|in:Debit,Kredit',
        ]);

        // 6. Jika validasi lolos, simpan data ke database
        ChartOfAccount::create([
            'kode_akun' => $request->kode_akun,
            'nama_akun' => $request->nama_akun,
            'tipe_akun' => $request->tipe_akun,
            'saldo_normal' => $request->saldo_normal,
        ]);

        // 7. Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('accounts.index')->with('success', 'Akun baru berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ChartOfAccount $account) // Ubah variabel $chartOfAccount jadi $account
    {
        // (Belum kita gunakan, bisa dilewati dulu)
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChartOfAccount $account) // Ubah variabel $chartOfAccount jadi $account
    {
        // 1. Tampilkan view 'edit' dan kirim data akun yang ingin diedit
        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChartOfAccount $account) // Ubah variabel $chartOfAccount jadi $account
    {
        // 2. Validasi data
        $request->validate([
            // Buat validasi 'unique' agar mengabaikan data miliknya sendiri
            'kode_akun' => 'required|string|max:20|unique:chart_of_accounts,kode_akun,' . $account->id,
            'nama_akun' => 'required|string|max:100',
            'tipe_akun' => 'required|in:Aset,Liabilitas,Ekuitas,Pendapatan,Biaya',
            'saldo_normal' => 'required|in:Debit,Kredit',
        ]);

        // 3. Update data di database
        $account->update([
            'kode_akun' => $request->kode_akun,
            'nama_akun' => $request->nama_akun,
            'tipe_akun' => $request->tipe_akun,
            'saldo_normal' => $request->saldo_normal,
        ]);

        // 4. Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChartOfAccount $account) // Gunakan variabel $account
{
    try {
        // 1. Hapus data dari database
        $account->delete();

        // 2. Redirect kembali dengan pesan sukses
        return redirect()->route('accounts.index')->with('success', 'Akun berhasil dihapus.');

    } catch (\Exception $e) {
        // 3. Jika terjadi error (misal, karena foreign key constraint)
        // Kirim pesan error
        return redirect()->route('accounts.index')->with('error', 'Gagal menghapus akun. Akun ini mungkin terkait dengan data lain.');
    }
}
}