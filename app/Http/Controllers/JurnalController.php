<?php

namespace App\Http\Controllers;

use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\ChartOfAccount;
use App\Models\Klinik; // Pastikan ini ada
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Pastikan ini ada
use Carbon\Carbon;

class JurnalController extends Controller
{
    /**
     * Display a listing of the resource filtered by clinic and role.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik', 'asc')->get();

        $selectedKlinikId = null;
        // Hanya Superadmin yang bisa memilih dari filter
        if ($user->hasRole('Superadmin')) {
            $selectedKlinikId = $request->input('klinik_id');
        } else {
            // Admin Klinik & Staf otomatis pakai klinik ID mereka
            $selectedKlinikId = $user->klinik_id;
            if (!$selectedKlinikId) {
                // Error jika Admin/Staf tidak punya klinik
                return view('jurnals.index', [
                    'periods' => collect(),
                    'kliniks' => $kliniks, // Tetap kirim kliniks untuk view Admin
                    'selectedKlinikId' => null,
                    'error' => 'Akun Anda belum terhubung ke klinik manapun. Hubungi Superadmin.'
                ]);
            }
        }

        $periodsQuery = Jurnal::select(
                DB::raw("DATE_FORMAT(tanggal_transaksi, '%Y-%m') as period_value"),
                DB::raw("DATE_FORMAT(tanggal_transaksi, '%M %Y') as period_name")
            )
            ->groupBy('period_value', 'period_name')
            ->orderBy('period_value', 'desc');

        // Filter berdasarkan klinik
        if ($selectedKlinikId) {
            $periodsQuery->where('klinik_id', $selectedKlinikId);
        } else if ($user->hasRole('Superadmin') && !$request->filled('klinik_id')) {
             // Superadmin belum pilih klinik -> tampilkan data kosong (minta filter dulu)
             $periodsQuery->where('klinik_id', -1); // ID -1 tidak akan pernah ada
        } // Admin/Staf sudah PASTI punya $selectedKlinikId di sini

        $periods = $periodsQuery->get();

        return view('jurnals.index', compact(
            'periods',
            'kliniks', // Kirim daftar klinik ke view (untuk filter Admin)
            'selectedKlinikId'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * Send relevant clinics based on user role.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Ambil akun global
        $globalAccounts = ChartOfAccount::whereNull('klinik_id')->orderBy('kode_akun', 'asc')->get();
        
        $kliniks = collect();
        $userKlinikId = null; 
        $clinicAccounts = collect(); // Akun spesifik klinik

        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik', 'asc')->get();
            // Superadmin awalnya hanya melihat akun global
            $accountsForView = $globalAccounts;
        } elseif ($user->klinik_id) {
            // Admin/Staf: ambil klinik mereka & akun spesifik klinik mereka
            $kliniks = Klinik::where('id', $user->klinik_id)->get();
            $userKlinikId = $user->klinik_id;
            $clinicAccounts = ChartOfAccount::where('klinik_id', $userKlinikId)->orderBy('kode_akun', 'asc')->get();
            // Gabungkan akun global dan akun klinik
            $accountsForView = $globalAccounts->merge($clinicAccounts)->sortBy('kode_akun');
        } else {
             // Admin/Staf tanpa klinik
             return redirect()->route('jurnals.index')->with('error', 'Akun Anda belum terhubung ke klinik. Tidak dapat membuat jurnal.');
        }

        return view('jurnals.create', compact(
            'accountsForView', // <-- Ganti nama variabel
            'kliniks', 
            'userKlinikId'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * Force clinic_id for non-Superadmin users.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Tentukan klinik ID yang akan disimpan
        $klinikIdToSave = null;
        if ($user->hasRole('Superadmin')) {
            $klinikIdToSave = $request->klinik_id;
        } else {
            // Admin Klinik & Staf, paksa gunakan klinik_id miliknya
            $klinikIdToSave = $user->klinik_id;
            if (!$klinikIdToSave) {
                 return back()->withInput()->with('error', 'Akun Anda belum terhubung ke klinik. Hubungi Superadmin.');
            }
        }

        $request->validate([
            'tanggal_transaksi' => 'required|date',
            // Validasi klinik_id hanya jika Superadmin
            'klinik_id' => $user->hasRole('Superadmin') ? 'required|integer|exists:kliniks,id' : 'nullable',
            'deskripsi' => 'required|string|max:255',
            'nomor_bukti' => 'nullable|string|max:50',
            'details' => 'required|array|min:2',
            'details.*.chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        // Cek Keseimbangan
        $totalDebit = collect($request->details)->sum('debit');
        $totalKredit = collect($request->details)->sum('kredit');

        if (round($totalDebit, 2) !== round($totalKredit, 2)) { // Gunakan round untuk presisi
            return back()->withInput()->with('error', 'Jurnal tidak seimbang! Total Debit harus sama dengan Total Kredit.');
        }

        DB::beginTransaction();
        try {
            $jurnal = Jurnal::create([
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'klinik_id' => $klinikIdToSave, // Gunakan klinik ID yang sudah ditentukan
                'nomor_bukti' => $request->nomor_bukti,
                'deskripsi' => $request->deskripsi,
            ]);

            // Simpan Jurnal Details
            foreach ($request->details as $detail) {
                if ($detail['debit'] > 0 || $detail['kredit'] > 0) {
                    $jurnal->details()->create([
                        'chart_of_account_id' => $detail['chart_of_account_id'],
                        'debit' => $detail['debit'],
                        'kredit' => $detail['kredit'],
                    ]);
                }
            }

            DB::commit();
            // Redirect kembali ke index DENGAN filter klinik yang baru disimpan
            return redirect()->route('jurnals.index', ['klinik_id' => $klinikIdToSave])
                           ->with('success', 'Jurnal Umum berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan jurnal: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan jurnal. Silakan coba lagi.');
        }
    }

    /**
     * Display the specified resource for a given clinic and period.
     * Includes authorization check based on role.
     */
    public function showMonthly(Klinik $klinik, $period)
    {
        $user = Auth::user();
        // Cek Otorisasi: Superadmin boleh lihat semua, Admin/Staf hanya kliniknya
        if (!$user->hasRole('Superadmin') && $user->klinik_id != $klinik->id) {
            abort(403, 'Anda tidak diizinkan mengakses data klinik ini.');
        }

        try {
            $date = Carbon::createFromFormat('Y-m', $period);
            $year = $date->year;
            $month = $date->month;
            $periodName = $klinik->nama_klinik . ' - ' . $date->format('F Y');
        } catch (\Exception $e) {
            $redirectParams = $user->hasRole('Superadmin') ? [] : ['klinik_id' => $user->klinik_id];
            return redirect()->route('jurnals.index', $redirectParams)->with('error', 'Format periode tidak valid.');
        }

        $jurnals = Jurnal::with(['details.account'])
            ->where('klinik_id', $klinik->id) // Filter klinik
            ->whereYear('tanggal_transaksi', $year)
            ->whereMonth('tanggal_transaksi', $month)
            ->orderBy('tanggal_transaksi', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalDebit = $jurnals->pluck('details')->flatten()->sum('debit');
        $totalKredit = $jurnals->pluck('details')->flatten()->sum('kredit');

        return view('jurnals.show', compact(
            'jurnals',
            'klinik', // Kirim klinik ke view untuk tombol kembali
            'periodName',
            'totalDebit',
            'totalKredit'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * Includes authorization check based on role.
     */
    public function edit(Jurnal $jurnal)
    {
        $user = Auth::user();
        // Cek Otorisasi ... (biarkan seperti sebelumnya) ...
        if (!$user->hasRole('Superadmin') && $user->klinik_id != $jurnal->klinik_id) {
            abort(403, 'Anda tidak diizinkan mengedit jurnal klinik ini.');
        }

        // Ambil akun global
        $globalAccounts = ChartOfAccount::whereNull('klinik_id')->orderBy('kode_akun', 'asc')->get();
        
        $kliniks = collect();
        $clinicAccounts = collect(); // Akun spesifik klinik

        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik', 'asc')->get();
            // Superadmin: Ambil akun klinik tempat jurnal ini dibuat
            if ($jurnal->klinik_id) {
                 $clinicAccounts = ChartOfAccount::where('klinik_id', $jurnal->klinik_id)->orderBy('kode_akun', 'asc')->get();
            }
             $accountsForView = $globalAccounts->merge($clinicAccounts)->sortBy('kode_akun');
        } elseif ($user->klinik_id) {
            // Admin/Staf: Ambil klinik mereka & akun spesifik klinik mereka
            $kliniks = Klinik::where('id', $user->klinik_id)->get();
             $clinicAccounts = ChartOfAccount::where('klinik_id', $user->klinik_id)->orderBy('kode_akun', 'asc')->get();
             $accountsForView = $globalAccounts->merge($clinicAccounts)->sortBy('kode_akun');
        } else {
             // Seharusnya tidak terjadi karena sudah dicek otorisasi
             abort(403);
        }


        $jurnal->load('details'); 

        return view('jurnals.edit', compact(
            'jurnal', 
            'accountsForView', // <-- Ganti nama variabel
            'kliniks'
            // userKlinikId tidak perlu di edit
        ));
    }

    /**
     * Update the specified resource in storage.
     * Includes authorization check and forces clinic_id for non-Superadmin.
     */
    public function update(Request $request, Jurnal $jurnal)
    {
        $user = Auth::user();
        // Cek Otorisasi
        if (!$user->hasRole('Superadmin') && $user->klinik_id != $jurnal->klinik_id) {
            abort(403, 'Anda tidak diizinkan mengupdate jurnal klinik ini.');
        }

        // Tentukan klinik ID yang akan disimpan
        $klinikIdToSave = null;
        if ($user->hasRole('Superadmin')) {
            $klinikIdToSave = $request->klinik_id;
        } else {
            $klinikIdToSave = $user->klinik_id; // Admin/Staf tidak bisa ubah klinik
            if (!$klinikIdToSave) {
                 return back()->withInput()->with('error', 'Akun Anda belum terhubung ke klinik.');
            }
        }

        $request->validate([
            'tanggal_transaksi' => 'required|date',
            // Validasi klinik_id hanya jika Superadmin
            'klinik_id' => $user->hasRole('Superadmin') ? 'required|integer|exists:kliniks,id' : 'nullable',
            'deskripsi' => 'required|string|max:255',
            'nomor_bukti' => 'nullable|string|max:50',
            'details' => 'required|array|min:2',
            'details.*.chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        // Cek Keseimbangan
        $totalDebit = collect($request->details)->sum('debit');
        $totalKredit = collect($request->details)->sum('kredit');

        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            return back()->withInput()->with('error', 'Jurnal tidak seimbang! Total Debit harus sama dengan Total Kredit.');
        }

        DB::beginTransaction();
        try {
            $jurnal->update([
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'klinik_id' => $klinikIdToSave, // Gunakan klinik ID yang ditentukan
                'nomor_bukti' => $request->nomor_bukti,
                'deskripsi' => $request->deskripsi,
            ]);

            // Hapus detail jurnal yang LAMA
            $jurnal->details()->delete();

            // Buat ulang detail jurnal yang BARU
            foreach ($request->details as $detail) {
                if ($detail['debit'] > 0 || $detail['kredit'] > 0) {
                    $jurnal->details()->create([
                        'chart_of_account_id' => $detail['chart_of_account_id'],
                        'debit' => $detail['debit'],
                        'kredit' => $detail['kredit'],
                    ]);
                }
            }

            DB::commit();
            $period = Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m');
            // Redirect ke showMonthly DENGAN klinik ID yang benar
            return redirect()->route('jurnals.showMonthly', ['klinik' => $klinikIdToSave, 'period' => $period])
                           ->with('success', 'Transaksi jurnal berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengupdate jurnal: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui jurnal.');
        }
    }

    /**
     * Remove the specified resource from storage.
     * Includes authorization check.
     */
    public function destroy(Jurnal $jurnal)
    {
        $user = Auth::user();
        // Cek Otorisasi
        if (!$user->hasRole('Superadmin') && $user->klinik_id != $jurnal->klinik_id) {
            abort(403, 'Anda tidak diizinkan menghapus jurnal klinik ini.');
        }

        $period = Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m');
        $klinikId = $jurnal->klinik_id; // Simpan klinik ID sebelum dihapus

        DB::beginTransaction(); // Gunakan transaction untuk keamanan
        try {
            // Hapus details dulu (meskipun ada cascade, ini lebih eksplisit)
            $jurnal->details()->delete();
            // Hapus jurnal induk
            $jurnal->delete();

            DB::commit();

            // Redirect ke showMonthly DENGAN klinik ID yang benar
            return redirect()->route('jurnals.showMonthly', ['klinik' => $klinikId, 'period' => $period])
                           ->with('success', 'Transaksi jurnal berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus jurnal: ' . $e->getMessage());
            // Redirect kembali ke halaman sebelumnya (detail bulan) jika gagal
            return back()->with('error', 'Gagal menghapus jurnal. Silakan coba lagi.');
        }
    }
}