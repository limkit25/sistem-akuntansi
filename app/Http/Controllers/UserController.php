<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role; // <-- 1. Tambahkan ini
use App\Models\Klinik;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash; // <-- Tambahkan ini di atas class
use Illuminate\Validation\Rules; // <-- Tambahkan ini di atas class



class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 2. Ambil semua user, beserta role-nya
        // Kita urutkan agar user 'Admin' (ID 1) selalu di atas
        $users = User::with('roles')->orderBy('id', 'asc')->get();
        
        // 3. Tampilkan view
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Ambil roles (kecuali Superadmin)
        $roles = Role::where('name', '!=', 'Superadmin')->get(); // Pastikan Role di-import
        // Ambil klinik yang aktif
        $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik')->get(); // Pastikan Klinik di-import

        // Pastikan kedua variabel dikirim ke view
        return view('users.create', compact('roles', 'kliniks'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => [
                'required',
                'string',
                 Rule::exists('roles', 'name')->whereNot('name', 'Superadmin') // Pastikan bukan Superadmin
            ],
            'klinik_id' => [
                'nullable', // Boleh null
                'integer',
                Rule::exists('kliniks', 'id'),
                // Wajib jika role BUKAN Superadmin
                Rule::requiredIf(function () use ($request) {
                    return $request->input('role') !== 'Superadmin';
                }),
                // Harus null jika role Superadmin (meskipun tidak bisa dipilih)
                Rule::excludeIf(function () use ($request) {
                    return $request->input('role') === 'Superadmin';
                }),
            ],
        ]);

        // 2. Tentukan Klinik ID
        // Jadi null HANYA jika rolenya Superadmin (meskipun tidak bisa dipilih)
        $klinikId = ($request->role == 'Superadmin') ? null : $request->klinik_id;

        // 3. Gunakan DB Transaction
        DB::beginTransaction();
        try {
            // Buat User baru
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'klinik_id' => $klinikId,
            ]);

            // Tetapkan Role
            $user->assignRole($request->role);

            DB::commit();
            return redirect()->route('users.index')->with('success', 'User baru berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membuat user baru: '.$e->getMessage());
            return back()->withInput()->with('error', 'Gagal menambahkan user baru.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // (Kita tidak pakai 'show')
        return abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();
        $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik')->get(); // <-- 2. Ambil klinik

        return view('users.edit', compact('user', 'roles', 'kliniks')); // <-- 3. Kirim kliniks
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // 1. Validasi (Diperbaiki)
        $request->validate([
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name'), // Validasi exists
                 // Rule kustom: Pastikan Superadmin (ID 1) tidak diubah rolenya
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->id == 1 && $value != 'Superadmin') {
                        $fail('Role Superadmin utama (ID 1) tidak boleh diubah.');
                    }
                },
            ],
            'klinik_id' => [
                'nullable', // Boleh null (untuk Superadmin)
                'integer',
                Rule::exists('kliniks', 'id'), // Validasi exists
                 // Rule kustom: Wajib jika role BUKAN Superadmin dan user BUKAN ID 1
                function ($attribute, $value, $fail) use ($request, $user) {
                    // Cek hanya jika user bukan ID 1
                    if ($user->id != 1) {
                         // Jika role yang dipilih bukan Superadmin DAN klinik_id kosong
                        if ($request->role != 'Superadmin' && empty($value)) {
                            $fail('User dengan role Admin atau Staf harus terhubung ke Klinik.');
                        }
                        // Jika role yang dipilih Superadmin tapi klinik_id diisi
                         if ($request->role == 'Superadmin' && !empty($value)) {
                            $fail('Superadmin tidak boleh terhubung ke klinik spesifik.');
                        }
                    }
                },
            ],
        ]);

        // 2. Tentukan Klinik ID berdasarkan Role (Diperbaiki)
        // Jadi null HANYA jika rolenya Superadmin (atau user ID 1)
        $klinikId = ($request->role == 'Superadmin' || $user->id == 1) ? null : $request->klinik_id;

        // 3. Gunakan DB Transaction
        DB::beginTransaction();
        try {
            // Update Role (Hanya jika user bukan ID 1)
            if ($user->id != 1) {
                 $user->syncRoles([$request->role]);
            } // Jika user ID 1, rolenya tidak akan berubah (sudah dicegah validasi)

            // Update Klinik ID
            $user->klinik_id = $klinikId;
            $user->save();

            DB::commit();
            return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal update user: '.$e->getMessage());
            return back()->withInput()->with('error', 'Gagal memperbarui data user.');
        }
    }

    /**
     * Remove the specified resource from storage.
     * Disesuaikan untuk Superadmin, Admin, Staf.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        // 1. Cegah user menghapus dirinya sendiri
        if ($user->id == auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // 2. Cegah penghapusan Superadmin utama (ID 1) atau user lain dengan role Superadmin (Diperbaiki)
        if ($user->id == 1 || $user->hasRole('Superadmin')) {
             return back()->with('error', 'Akun Superadmin tidak dapat dihapus.');
        }

        // 3. Hapus user (Admin Klinik dan Staf boleh dihapus)
        DB::beginTransaction(); // Gunakan transaction
        try {
            // Hapus roles user dulu (best practice)
            $user->syncRoles([]);
            // Hapus user
            $user->delete();
            DB::commit();
            return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
        } catch (\Exception $e) {
             DB::rollBack();
             Log::error('Gagal hapus user: '.$e->getMessage());
             return back()->with('error', 'Gagal menghapus user.');
        }
    }

}