<?php

namespace App\Http\Controllers;

use App\Models\Klinik;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KlinikController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kliniks = Klinik::orderBy('nama_klinik', 'asc')->get();
        return view('kliniks.index', compact('kliniks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('kliniks.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_klinik' => 'required|string|max:255',
            'kode_klinik' => 'nullable|string|max:10|unique:kliniks',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
        ]);

        Klinik::create($request->all());

        return redirect()->route('kliniks.index')
                         ->with('success', 'Klinik baru berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Klinik $klinik)
    {
        // Kita tidak pakai halaman show (lihat detail)
        return redirect()->route('kliniks.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Klinik $klinik)
    {
        // Langsung tampilkan view dengan data klinik yang dipilih
        return view('kliniks.edit', compact('klinik'));
    }

    /**
     * Update the specified resource in storage.
     * Hanya bisa diakses Superadmin (via route middleware).
     */
    public function update(Request $request, Klinik $klinik)
    {
        // Validasi, pastikan kode unik (kecuali untuk dirinya sendiri)
        $request->validate([
            'nama_klinik' => 'required|string|max:255',
            'kode_klinik' => [
                'nullable',
                'string',
                'max:10',
                Rule::unique('kliniks')->ignore($klinik->id), // Abaikan ID saat ini
            ],
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
            'is_active' => 'required|boolean', // Pastikan status dikirim
        ]);

        // Update data klinik
        $klinik->update($request->all());

        // Redirect kembali ke daftar klinik
        return redirect()->route('kliniks.index')
                         ->with('success', 'Data klinik berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     * Hanya bisa diakses Superadmin (via route middleware).
     */
     public function destroy(Klinik $klinik)
     {
         // Cek apakah klinik ini digunakan oleh User
         if ($klinik->users()->exists()) { // Perlu definisikan relasi 'users' di Model Klinik
             return back()->with('error', 'Gagal menghapus. Klinik ini sedang digunakan oleh user.');
         }
         // Cek apakah klinik ini digunakan oleh Jurnal (jika foreign key restrict)
         if ($klinik->jurnals()->exists()) { // Perlu definisikan relasi 'jurnals' di Model Klinik
             return back()->with('error', 'Gagal menghapus. Klinik ini memiliki data transaksi jurnal.');
         }
          // Cek apakah klinik ini digunakan oleh COA (jika foreign key restrict)
         if ($klinik->accounts()->exists()) { // Relasi 'accounts' sudah ada
             return back()->with('error', 'Gagal menghapus. Klinik ini memiliki data Bagan Akun (COA).');
         }


         try {
             $klinik->delete();
             return redirect()->route('kliniks.index')
                              ->with('success', 'Klinik berhasil dihapus.');
         } catch (\Exception $e) {
             // Tangkap error lain jika ada
             \Log::error('Gagal hapus klinik: '.$e->getMessage());
             return back()->with('error', 'Gagal menghapus klinik. Terjadi kesalahan.');
         }
     }

}