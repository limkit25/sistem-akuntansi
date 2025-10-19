<?php

namespace App\Http\Controllers;

use App\Models\Jurnal;
use App\Models\JurnalDetail;      // <-- 1. Tambahkan
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- 2. Tambahkan
use Illuminate\Support\Facades\Log; // <-- 3. Tambahkan (untuk error)
use Carbon\Carbon;

class JurnalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil daftar periode (Tahun-Bulan) yang unik dari tabel jurnals
        $periods = Jurnal::select(
                DB::raw("DATE_FORMAT(tanggal_transaksi, '%Y-%m') as period_value"), // Format: 2025-10
                DB::raw("DATE_FORMAT(tanggal_transaksi, '%M %Y') as period_name") // Format: Oktober 2025
            )
            ->groupBy('period_value', 'period_name')
            ->orderBy('period_value', 'desc') // Urutkan dari yang terbaru
            ->get();

        // Tampilkan view index dan kirim datanya
        return view('jurnals.index', compact('periods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = ChartOfAccount::orderBy('kode_akun', 'asc')->get();
        return view('jurnals.create', compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 4. Validasi data
        $request->validate([
            'tanggal_transaksi' => 'required|date',
            'deskripsi' => 'required|string|max:255',
            'nomor_bukti' => 'nullable|string|max:50',
            'details' => 'required|array|min:2', // Pastikan ada minimal 2 baris (debit & kredit)
            'details.*.chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        // 5. Cek Keseimbangan (Balance)
        $totalDebit = 0;
        $totalKredit = 0;
        foreach ($request->details as $detail) {
            $totalDebit += $detail['debit'];
            $totalKredit += $detail['kredit'];
        }

        if ($totalDebit !== $totalKredit) {
            // Jika tidak balance, kembalikan dengan error
            return back()->withInput()->with('error', 'Jurnal tidak seimbang! Total Debit harus sama dengan Total Kredit.');
        }

        // 6. Mulai Database Transaction
        DB::beginTransaction();
        try {
            
            // 7. Simpan Jurnal (Induk)
            $jurnal = Jurnal::create([
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'nomor_bukti' => $request->nomor_bukti,
                'deskripsi' => $request->deskripsi,
            ]);

            // 8. Simpan Jurnal Details (Anak)
            foreach ($request->details as $detail) {
                // Hanya simpan jika debit atau kredit tidak 0
                if ($detail['debit'] > 0 || $detail['kredit'] > 0) {
                    $jurnal->details()->create([
                        'chart_of_account_id' => $detail['chart_of_account_id'],
                        'debit' => $detail['debit'],
                        'kredit' => $detail['kredit'],
                    ]);
                }
            }

            // 9. Jika semua berhasil, commit transaction
            DB::commit();

            // 10. Redirect ke halaman index dengan pesan sukses
            return redirect()->route('jurnals.index')->with('success', 'Jurnal Umum berhasil disimpan.');

        } catch (\Exception $e) {
            // 11. Jika terjadi error, rollback semua
            DB::rollBack();
            
            // Catat error (opsional tapi bagus)
            Log::error('Gagal menyimpan jurnal: ' . $e->getMessage());

            // Redirect kembali dengan pesan error
            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan jurnal. Silakan coba lagi.');
        }
    }

    public function showMonthly($period) // $period akan berisi "2025-10"
    {
        // 2. Parse string periode
        try {
            $date = Carbon::createFromFormat('Y-m', $period);
            $year = $date->year;
            $month = $date->month;
            $periodName = $date->format('F Y'); // Format: "October 2025"
        } catch (\Exception $e) {
            // Jika formatnya salah, kembalikan ke index
            return redirect()->route('jurnals.index')->with('error', 'Format periode tidak valid.');
        }

        // 3. Ambil semua jurnal di bulan & tahun tersebut
        // Kita gunakan 'with' (Eager Loading) untuk mengambil 'details' dan 'details.account'
        // Ini jauh lebih efisien daripada query di dalam loop
        $jurnals = Jurnal::with(['details.account'])
            ->whereYear('tanggal_transaksi', $year)
            ->whereMonth('tanggal_transaksi', $month)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get();

        // 4. Hitung total Debit & Kredit untuk periode ini
        $totalDebit = 0;
        $totalKredit = 0;
        foreach ($jurnals as $jurnal) {
            $totalDebit += $jurnal->details->sum('debit');
            $totalKredit += $jurnal->details->sum('kredit');
        }

        // 5. Kirim data ke view
        return view('jurnals.show', compact(
            'jurnals', 
            'periodName', 
            'totalDebit', 
            'totalKredit'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Jurnal $jurnal)
    {
        // 1. Ambil semua akun untuk dropdown
        $accounts = ChartOfAccount::orderBy('kode_akun', 'asc')->get();
        
        // 2. Muat relasi 'details' dari jurnal yang dipilih
        //    Ini akan kita kirim ke JavaScript di view
        $jurnal->load('details'); 

        // 3. Tampilkan view 'jurnals.edit' (yang akan kita buat)
        return view('jurnals.edit', compact('jurnal', 'accounts'));
    }
    public function destroy(Jurnal $jurnal)
    {
        // 1. Ambil periode (Tahun-Bulan) dari jurnal SEBELUM dihapus.
        //    Ini penting agar kita bisa kembali ke halaman yang benar.
        $period = \Carbon\Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m');

        try {
            // 2. Hapus data jurnal (induk).
            //    Karena kita sudah mengatur onDelete('cascade') di migrasi,
            //    semua 'jurnal_details' (anak) akan otomatis ikut terhapus.
            $jurnal->delete();
            
            // 3. Redirect kembali ke halaman detail bulanan, BUKAN ke index.
            return redirect()->route('jurnals.showMonthly', $period)
                           ->with('success', 'Transaksi jurnal berhasil dihapus.');

        } catch (\Exception $e) {
            // Tangkap jika ada error lain
            Log::error('Gagal menghapus jurnal: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus jurnal. Silakan coba lagi.');
        }
    }
    public function update(Request $request, Jurnal $jurnal)
    {
        // 1. Validasi data (sama seperti store)
        $request->validate([
            'tanggal_transaksi' => 'required|date',
            'deskripsi' => 'required|string|max:255',
            'nomor_bukti' => 'nullable|string|max:50',
            'details' => 'required|array|min:2',
            'details.*.chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        // 2. Cek Keseimbangan (Balance)
        $totalDebit = 0;
        $totalKredit = 0;
        foreach ($request->details as $detail) {
            $totalDebit += $detail['debit'];
            $totalKredit += $detail['kredit'];
        }

        if ($totalDebit !== $totalKredit) {
            return back()->withInput()->with('error', 'Jurnal tidak seimbang! Total Debit harus sama dengan Total Kredit.');
        }

        // 3. Mulai Database Transaction
        DB::beginTransaction();
        try {
            
            // 4. Update Jurnal (Induk)
            $jurnal->update([
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'nomor_bukti' => $request->nomor_bukti,
                'deskripsi' => $request->deskripsi,
            ]);

            // 5. Hapus semua detail jurnal yang LAMA
            $jurnal->details()->delete();

            // 6. Buat ulang detail jurnal yang BARU (sama seperti store)
            foreach ($request->details as $detail) {
                if ($detail['debit'] > 0 || $detail['kredit'] > 0) {
                    $jurnal->details()->create([
                        'chart_of_account_id' => $detail['chart_of_account_id'],
                        'debit' => $detail['debit'],
                        'kredit' => $detail['kredit'],
                    ]);
                }
            }

            // 7. Commit transaction
            DB::commit();

            // 8. Ambil periode (Tahun-Bulan) untuk redirect kembali
            $period = \Carbon\Carbon::parse($jurnal->tanggal_transaksi)->format('Y-m');

            return redirect()->route('jurnals.showMonthly', $period)
                           ->with('success', 'Transaksi jurnal berhasil diperbarui.');

        } catch (\Exception $e) {
            // 9. Jika error, rollback
            DB::rollBack();
            Log::error('Gagal mengupdate jurnal: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui jurnal.');
        }
    }
}
