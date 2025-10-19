<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\JurnalDetail;
use App\Models\Jurnal;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // <-- Pastikan ini ada

class LaporanController extends Controller
{
    /**
     * Menampilkan halaman filter dan laporan Buku Besar.
     */
    public function bukuBesar(Request $request)
    {
        // 1. Ambil semua akun untuk dropdown filter
        $accounts = ChartOfAccount::orderBy('kode_akun', 'asc')->get();

        // 2. Siapkan variabel
        $data = collect();
        $saldoAwal = 0;
        $akunDipilih = null;
        // Ambil filter dari request, atau set default jika tidak ada
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        // 3. Cek jika user sudah melakukan filter (mengirim 'akun_id')
        if ($request->filled('akun_id')) {
            $akun_id = $request->input('akun_id');
            $akunDipilih = ChartOfAccount::find($akun_id);

            // --- HITUNG SALDO AWAL (Opening Balance) ---
            // Ambil total debit & kredit SEBELUM start_date
            $saldoAwalDebits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $akun_id)
                ->where('jurnals.tanggal_transaksi', '<', $startDate)
                ->sum('jurnal_details.debit');
                
            $saldoAwalKredits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $akun_id)
                ->where('jurnals.tanggal_transaksi', '<', $startDate)
                ->sum('jurnal_details.kredit');

            // Hitung saldo awal bersih berdasarkan saldo normal akun
            if ($akunDipilih->saldo_normal == 'Debit') {
                $saldoAwal = $saldoAwalDebits - $saldoAwalKredits;
            } else { // Saldo normal 'Kredit'
                $saldoAwal = $saldoAwalKredits - $saldoAwalDebits;
            }

            // --- AMBIL MUTASI TRANSAKSI ---
            // Ambil semua detail jurnal PADA rentang tanggal
            $data = JurnalDetail::with('jurnal') // Eager load data jurnal (induk)
                ->join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $akun_id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->orderBy('jurnals.tanggal_transaksi', 'asc') // Urutkan berdasarkan tanggal
                ->orderBy('jurnals.id', 'asc') // Jika tanggal sama, urutkan berdasarkan ID
                ->select('jurnal_details.*') // Ambil semua dari jurnal_details
                ->get();
        }
        
        // 4. Kirim data ke view
        return view('laporan.buku-besar', compact(
            'accounts',
            'data',
            'saldoAwal',
            'akunDipilih',
            'startDate', // Kirim tanggal filter kembali ke view
            'endDate'    // Kirim tanggal filter kembali ke view
        ));
    }
    public function neracaSaldo(Request $request)
    {
        // 1. Ambil filter tanggal. Defaultnya adalah akhir bulan ini.
        //    Tidak seperti Buku Besar, Neraca Saldo hanya butuh 1 tanggal (Tanggal Akhir).
        $endDate = $request->input('end_date', date('Y-m-t'));

        // 2. Ambil semua akun
        $accounts = ChartOfAccount::orderBy('kode_akun', 'asc')->get();

        $laporanData = [];
        $totalDebit = 0;
        $totalKredit = 0;

        // 3. Loop setiap akun untuk menghitung saldo akhirnya
        foreach ($accounts as $account) {
            // Ambil total debit dan kredit untuk akun ini, dari awal s/d end_date
            $totalDebitsForAccount = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->where('jurnals.tanggal_transaksi', '<=', $endDate)
                ->sum('jurnal_details.debit');
                
            $totalKreditsForAccount = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->where('jurnals.tanggal_transaksi', '<=', $endDate)
                ->sum('jurnal_details.kredit');

            // Hitung saldo bersih
            $saldo = 0;
            $saldoDebit = 0;
            $saldoKredit = 0;

            if ($account->saldo_normal == 'Debit') {
                $saldo = $totalDebitsForAccount - $totalKreditsForAccount;
            } else { // Saldo normal 'Kredit'
                $saldo = $totalKreditsForAccount - $totalDebitsForAccount;
            }

            // Hanya tampilkan jika saldonya tidak nol
            if ($saldo != 0) {
                // Masukkan ke kolom Debit atau Kredit berdasarkan saldo normal
                if ($account->saldo_normal == 'Debit') {
                    $saldoDebit = $saldo;
                    $totalDebit += $saldo;
                } else {
                    $saldoKredit = $saldo;
                    $totalKredit += $saldo;
                }
                
                $laporanData[] = [
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'debit' => $saldoDebit,
                    'kredit' => $saldoKredit,
                ];
            }
        }
        
        // 4. Kirim data ke view
        return view('laporan.neraca-saldo', compact(
            'laporanData',
            'totalDebit',
            'totalKredit',
            'endDate'
        ));
    }
    public function labaRugi(Request $request)
    {
        // 1. Ambil filter tanggal. Defaultnya adalah bulan ini.
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        // 2. Siapkan variabel
        $listPendapatan = [];
        $listBiaya = [];
        $totalPendapatan = 0;
        $totalBiaya = 0;

        // 3. Ambil semua akun PENDAPATAN
        $pendapatanAccounts = ChartOfAccount::where('tipe_akun', 'Pendapatan')
                                            ->orderBy('kode_akun', 'asc')->get();
        
        foreach ($pendapatanAccounts as $account) {
            // Hitung total transaksi (mutasi) HANYA pada rentang tanggal
            $totalDebits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.debit');
                
            $totalKredits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.kredit');

            // Pendapatan saldo normalnya di KREDIT
            $saldo = $totalKredits - $totalDebits;

            if ($saldo != 0) {
                $listPendapatan[] = [
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'total' => $saldo
                ];
                $totalPendapatan += $saldo;
            }
        }

        // 4. Ambil semua akun BIAYA
        $biayaAccounts = ChartOfAccount::where('tipe_akun', 'Biaya')
                                       ->orderBy('kode_akun', 'asc')->get();

        foreach ($biayaAccounts as $account) {
            // Hitung total transaksi (mutasi) HANYA pada rentang tanggal
            $totalDebits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.debit');
                
            $totalKredits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.kredit');

            // Biaya saldo normalnya di DEBIT
            $saldo = $totalDebits - $totalKredits;

            if ($saldo != 0) {
                $listBiaya[] = [
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'total' => $saldo
                ];
                $totalBiaya += $saldo;
            }
        }
        
        // 5. Hitung Laba/Rugi Bersih
        $labaRugi = $totalPendapatan - $totalBiaya;

        // 6. Kirim data ke view
        return view('laporan.laba-rugi', compact(
            'listPendapatan',
            'listBiaya',
            'totalPendapatan',
            'totalBiaya',
            'labaRugi',
            'startDate',
            'endDate'
        ));
    }
    public function neraca(Request $request)
    {
        // 1. Ambil filter tanggal. Defaultnya adalah akhir bulan ini.
        $endDate = $request->input('end_date', date('Y-m-t'));
        // Tentukan tanggal awal tahun (untuk menghitung Laba Rugi Berjalan)
        $yearStartDate = Carbon::parse($endDate)->startOfYear()->format('Y-m-d');

        // --- A. KELOMPOK ASET ---
        $listAset = [];
        $totalAset = 0;
        $asetAccounts = ChartOfAccount::where('tipe_akun', 'Aset')->orderBy('kode_akun', 'asc')->get();

        foreach ($asetAccounts as $account) {
            $saldo = $this->hitungSaldoAkun($account, $endDate);
            if ($saldo != 0) {
                $listAset[] = [
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'total' => $saldo
                ];
                $totalAset += $saldo;
            }
        }

        // --- B. KELOMPOK LIABILITAS ---
        $listLiabilitas = [];
        $totalLiabilitas = 0;
        $liabilitasAccounts = ChartOfAccount::where('tipe_akun', 'Liabilitas')->orderBy('kode_akun', 'asc')->get();

        foreach ($liabilitasAccounts as $account) {
            $saldo = $this->hitungSaldoAkun($account, $endDate);
            if ($saldo != 0) {
                $listLiabilitas[] = [
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'total' => $saldo
                ];
                $totalLiabilitas += $saldo;
            }
        }

        // --- C. KELOMPOK EKUITAS ---
        $listEkuitas = [];
        $totalEkuitas = 0;
        $ekuitasAccounts = ChartOfAccount::where('tipe_akun', 'Ekuitas')->orderBy('kode_akun', 'asc')->get();

        foreach ($ekuitasAccounts as $account) {
            $saldo = $this->hitungSaldoAkun($account, $endDate);
            if ($saldo != 0) {
                $listEkuitas[] = [
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'total' => $saldo
                ];
                $totalEkuitas += $saldo;
            }
        }
        
        // --- D. LABA RUGI BERJALAN (Current Year's Net Income) ---
        // Kita hitung Laba Rugi dari Awal Tahun s/d Tanggal Laporan
        $labaRugiBerjalan = $this->hitungLabaRugi($yearStartDate, $endDate);
        
        // Tambahkan Laba Rugi Berjalan ke Total Ekuitas
        $totalEkuitas += $labaRugiBerjalan;
        $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitas;
        
        // 6. Kirim data ke view
        return view('laporan.neraca', compact(
            'listAset', 'totalAset',
            'listLiabilitas', 'totalLiabilitas',
            'listEkuitas', 'totalEkuitas',
            'labaRugiBerjalan',
            'totalLiabilitasDanEkuitas',
            'endDate'
        ));
    }

    /**
     * Fungsi helper untuk menghitung saldo akhir sebuah akun s/d tanggal tertentu.
     */
    private function hitungSaldoAkun($account, $endDate)
    {
        $totalDebits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
            ->where('jurnal_details.chart_of_account_id', $account->id)
            ->where('jurnals.tanggal_transaksi', '<=', $endDate)
            ->sum('jurnal_details.debit');
            
        $totalKredits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
            ->where('jurnal_details.chart_of_account_id', $account->id)
            ->where('jurnals.tanggal_transaksi', '<=', $endDate)
            ->sum('jurnal_details.kredit');

        if ($account->saldo_normal == 'Debit') {
            return $totalDebits - $totalKredits;
        } else { // Saldo normal 'Kredit'
            return $totalKredits - $totalDebits;
        }
    }

    /**
     * Fungsi helper untuk menghitung Laba Rugi pada rentang tanggal tertentu.
     */
    private function hitungLabaRugi($startDate, $endDate)
    {
        $totalPendapatan = 0;
        $pendapatanAccounts = ChartOfAccount::where('tipe_akun', 'Pendapatan')->get();
        foreach ($pendapatanAccounts as $account) {
            $totalDebits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.debit');
            $totalKredits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.kredit');
            $totalPendapatan += ($totalKredits - $totalDebits); // Saldo normal Kredit
        }

        $totalBiaya = 0;
        $biayaAccounts = ChartOfAccount::where('tipe_akun', 'Biaya')->get();
        foreach ($biayaAccounts as $account) {
            $totalDebits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.debit');
            $totalKredits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
                ->where('jurnal_details.chart_of_account_id', $account->id)
                ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate])
                ->sum('jurnal_details.kredit');
            $totalBiaya += ($totalDebits - $totalKredits); // Saldo normal Debit
        }

        return $totalPendapatan - $totalBiaya;
    }
}