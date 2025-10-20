<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\JurnalDetail;
use App\Models\Jurnal;
use App\Models\Klinik; // Pastikan ini ada
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth; // Pastikan ini ada
use app\models\user;
use Maatwebsite\Excel\Facades\Excel; // <-- Tambahkan
use App\Exports\BukuBesarExport;   // <-- Tambahkan
use App\Exports\NeracaSaldoExport;
use App\Exports\LabaRugiExport;
use App\Exports\NeracaExport;

class LaporanController extends Controller
{
    /**
     * Menampilkan halaman filter dan laporan Buku Besar.
     * Disesuaikan untuk filter klinik berdasarkan role.
     */
    /**
     * Menampilkan halaman filter dan laporan Buku Besar.
     * Disesuaikan untuk filter klinik berdasarkan role.
     * Dropdown akun difilter berdasarkan klinik yang dipilih.
     */
    public function bukuBesar(Request $request)
    {
        $user = Auth::user();

        // Ambil klinik yang relevan untuk filter
        $kliniks = collect();
        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik', 'asc')->get();
        } elseif ($user->klinik_id) {
            $kliniks = Klinik::where('id', $user->klinik_id)->get();
        }

        // Tentukan Klinik ID yang akan difilter
        $klinikIdFilter = null;
        if ($user->hasRole('Superadmin')) {
            // Jika Superadmin memilih 'global' di filter, simpan sebagai string
            if ($request->input('klinik_id') === 'global') {
                $klinikIdFilter = 'global';
            } else {
                // Jika memilih ID klinik, simpan sebagai integer
                $klinikIdFilter = $request->input('klinik_id');
            }
        } else {
            $klinikIdFilter = $user->klinik_id;
            if (!$klinikIdFilter && !$user->hasRole('Superadmin')) {
                 return view('laporan.buku-besar', [
                     'accountsForDropdown' => collect(),
                     'kliniks' => $kliniks, 'data' => collect(), 'saldoAwal' => 0,
                     'akunDipilih' => null, 'klinikDipilih' => null,
                     'startDate' => $request->input('start_date', date('Y-m-01')),
                     'endDate' => $request->input('end_date', date('Y-m-t')),
                     'klinikIdFilter' => null, 'akunIdFilter' => $request->input('akun_id'),
                     'error' => 'Akun Anda belum terhubung ke klinik.'
                 ]);
             }
        }

        // Ambil filter lain dari request
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));
        $akunIdFilter = $request->input('akun_id');

        // Ambil akun UNTUK DROPDOWN berdasarkan klinik yang difilter
        $accountsForDropdown = collect();
        if ($klinikIdFilter === 'global') {
             // Tampilkan hanya akun global
            $accountsForDropdown = ChartOfAccount::whereNull('klinik_id')
                                    ->orderBy('kode_akun', 'asc')
                                    ->get();
        } elseif (is_numeric($klinikIdFilter)) {
            // Ambil akun global DAN akun spesifik klinik yang dipilih
            $accountsForDropdown = ChartOfAccount::where(function($query) use ($klinikIdFilter) {
                                        $query->whereNull('klinik_id')
                                              ->orWhere('klinik_id', $klinikIdFilter);
                                    })
                                    ->orderByRaw('klinik_id IS NULL DESC, kode_akun ASC')
                                    ->get();
        } elseif ($user->hasRole('Superadmin') && !$request->filled('klinik_id')) {
             // Superadmin belum filter klinik, dropdown akun kosong
             $accountsForDropdown = collect();
        }
        // Admin/Staf tanpa klinik sudah dihandle

        // Siapkan variabel hasil laporan
        $data = collect();
        $saldoAwal = 0;
        $akunDipilih = null;
        $klinikDipilih = null; // Ini akan berisi model Klinik jika dipilih

        // --- Proses Laporan HANYA JIKA klinik SPESIFIK/GLOBAL dan akun sudah dipilih ---
        if ($akunIdFilter && ($klinikIdFilter === 'global' || is_numeric($klinikIdFilter))) {
            $akunDipilih = ChartOfAccount::find($akunIdFilter);
            // Tentukan ID klinik aktual untuk query (null jika global)
            $actualKlinikId = is_numeric($klinikIdFilter) ? (int)$klinikIdFilter : null;
            if (is_numeric($klinikIdFilter)) {
                 $klinikDipilih = Klinik::find($klinikIdFilter); // Ambil model Klinik
            }


            // Validasi Akun vs Klinik
            $isValidAccount = false;
            if ($akunDipilih) {
                if ($klinikIdFilter === 'global' && $akunDipilih->klinik_id === null) {
                    $isValidAccount = true; // Akun global valid untuk filter global
                } elseif (is_numeric($klinikIdFilter) && ($akunDipilih->klinik_id === null || $akunDipilih->klinik_id == $actualKlinikId)) {
                    $isValidAccount = true; // Akun global atau akun klinik yg benar valid
                }
            }


            if (!$isValidAccount) {
                 $akunDipilih = null; // Reset akunDipilih
                 $data = collect();
                 session()->flash('warning', 'Akun yang dipilih tidak valid untuk konteks klinik ini.');
            } else {
                // --- HITUNG SALDO AWAL & MUTASI ---
                $saldoAwal = $this->hitungSaldoAwal($akunDipilih, $startDate, $actualKlinikId);
                $data = $this->ambilMutasi($akunDipilih, $startDate, $endDate, $actualKlinikId);
            }
        }

        // Kirim data ke view
        return view('laporan.buku-besar', compact(
            'accountsForDropdown',
            'kliniks',
            'data',
            'saldoAwal',
            'akunDipilih',
            'klinikDipilih', // Kirim model Klinik atau null
            'startDate',
            'endDate',
            'klinikIdFilter', // Kirim 'global' atau ID
            'akunIdFilter'
        ));
    }
    /**
     * Menampilkan laporan Neraca Saldo.
     * Disesuaikan untuk filter klinik berdasarkan role.
     */
    public function neracaSaldo(Request $request)
    {
        $user = Auth::user();

        // Ambil klinik yang relevan
        $kliniks = collect();
        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik', 'asc')->get();
        } elseif ($user->klinik_id) {
            $kliniks = Klinik::where('id', $user->klinik_id)->get(); // Hanya klinik user
        }

        // Tentukan Klinik ID yang akan difilter
        $klinikIdFilter = null;
        if ($user->hasRole('Superadmin')) {
            $klinikIdFilter = $request->input('klinik_id');
        } else {
            $klinikIdFilter = $user->klinik_id;
            if (!$klinikIdFilter && !$user->hasRole('Superadmin')) {
                 return view('laporan.neraca-saldo', [
                     'kliniks' => $kliniks, 'laporanData' => [],
                     'totalDebit' => 0, 'totalKredit' => 0,
                     'endDate' => $request->input('end_date', date('Y-m-t')),
                     'klinikDipilih' => null, 'klinikIdFilter' => null,
                     'error' => 'Akun Anda belum terhubung ke klinik.'
                 ]);
            }
        }

        $endDate = $request->input('end_date', date('Y-m-t'));
        // Ambil model Klinik jika ID numerik
        $klinikDipilih = is_numeric($klinikIdFilter) ? Klinik::find($klinikIdFilter) : null;

        $laporanData = [];
        $totalDebit = 0;
        $totalKredit = 0;

        // Hanya proses jika klinik sudah dipilih/ditentukan (harus numerik ID)
        if (is_numeric($klinikIdFilter)) {
            // Ambil akun global DAN akun spesifik klinik yang dipilih
            $accounts = ChartOfAccount::where(function($query) use ($klinikIdFilter) {
                                $query->whereNull('klinik_id')
                                      ->orWhere('klinik_id', $klinikIdFilter);
                            })
                            ->orderBy('kode_akun', 'asc')
                            ->get();

            foreach ($accounts as $account) {
                // Hitung saldo HANYA untuk klinik yang dipilih
                // (Fungsi hitungSaldoAkun sudah diupdate)
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);

                if (round($saldo, 2) != 0) { // Gunakan round
                    $saldoDebit = ($account->saldo_normal == 'Debit') ? $saldo : 0;
                    $saldoKredit = ($account->saldo_normal == 'Kredit') ? $saldo : 0;

                    $totalDebit += $saldoDebit;
                    $totalKredit += $saldoKredit;

                    $laporanData[] = [
                        'kode_akun' => $account->kode_akun,
                        'nama_akun' => $account->nama_akun,
                        'debit' => $saldoDebit,
                        'kredit' => $saldoKredit,
                    ];
                }
            }
        } // Akhir if(is_numeric($klinikIdFilter))

        return view('laporan.neraca-saldo', compact(
            'kliniks',
            'laporanData',
            'totalDebit',
            'totalKredit',
            'endDate',
            'klinikDipilih', // Kirim model Klinik atau null
            'klinikIdFilter' // Kirim ID klinik yang difilter
        ));
    }

    /**
     * Menampilkan laporan Laba Rugi.
     * Disesuaikan untuk filter klinik berdasarkan role.
     */
    public function labaRugi(Request $request)
    {
        $user = Auth::user();

        // Ambil klinik yang relevan
        $kliniks = collect();
        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik', 'asc')->get();
        } elseif ($user->klinik_id) {
            $kliniks = Klinik::where('id', $user->klinik_id)->get();
        }

        // Tentukan Klinik ID
        $klinikIdFilter = null;
        if ($user->hasRole('Superadmin')) {
            $klinikIdFilter = $request->input('klinik_id');
        } else {
            $klinikIdFilter = $user->klinik_id;
             if (!$klinikIdFilter && !$user->hasRole('Superadmin')) {
                 return view('laporan.laba-rugi', [
                     'kliniks' => $kliniks, 'listPendapatan' => [], 'listBiaya' => [],
                     'totalPendapatan' => 0, 'totalBiaya' => 0, 'labaRugi' => 0,
                     'startDate' => $request->input('start_date', date('Y-m-01')),
                     'endDate' => $request->input('end_date', date('Y-m-t')),
                     'klinikDipilih' => null, 'klinikIdFilter' => null,
                     'error' => 'Akun Anda belum terhubung ke klinik.'
                 ]);
             }
        }

        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));
        $klinikDipilih = is_numeric($klinikIdFilter) ? Klinik::find($klinikIdFilter) : null; // Ambil model klinik

        $listPendapatan = [];
        $listBiaya = [];
        $totalPendapatan = 0;
        $totalBiaya = 0;
        $labaRugi = 0;

        // Hanya proses jika klinik sudah dipilih/ditentukan (harus numerik ID)
        if (is_numeric($klinikIdFilter)) {
            // Ambil akun global DAN akun spesifik klinik
             $pendapatanAccounts = ChartOfAccount::where('tipe_akun', 'Pendapatan')
                                                ->where(function($q) use ($klinikIdFilter){
                                                     $q->whereNull('klinik_id')
                                                       ->orWhere('klinik_id', $klinikIdFilter);
                                                })
                                                ->orderBy('kode_akun', 'asc')->get();

            foreach ($pendapatanAccounts as $account) {
                // Gunakan fungsi helper hitungMutasiAkun (sudah diupdate)
                $saldo = $this->hitungMutasiAkun($account, $startDate, $endDate, $klinikIdFilter);
                if ($saldo != 0) {
                    $listPendapatan[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalPendapatan += $saldo;
                }
            }

            // Ambil akun global DAN akun spesifik klinik
             $biayaAccounts = ChartOfAccount::where('tipe_akun', 'Biaya')
                                           ->where(function($q) use ($klinikIdFilter){
                                                $q->whereNull('klinik_id')
                                                  ->orWhere('klinik_id', $klinikIdFilter);
                                           })
                                           ->orderBy('kode_akun', 'asc')->get();
            foreach ($biayaAccounts as $account) {
                 // Gunakan fungsi helper hitungMutasiAkun (sudah diupdate)
                $saldo = $this->hitungMutasiAkun($account, $startDate, $endDate, $klinikIdFilter);
                if ($saldo != 0) {
                    $listBiaya[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalBiaya += $saldo;
                }
            }
            $labaRugi = $totalPendapatan - $totalBiaya;
        } // Akhir if(is_numeric($klinikIdFilter))

        return view('laporan.laba-rugi', compact(
            'kliniks',
            'listPendapatan', 'listBiaya',
            'totalPendapatan', 'totalBiaya',
            'labaRugi',
            'startDate', 'endDate',
            'klinikDipilih', // Kirim model klinik
            'klinikIdFilter' // Kirim ID klinik filter
        ));
    }

    // Pastikan fungsi helper hitungMutasiAkun sudah ada
    /**
      * Fungsi helper untuk menghitung MUTASI akun pada rentang tanggal dan klinik tertentu.
      */
     private function hitungMutasiAkun($account, $startDate, $endDate, $klinikId)
     {
         $query = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
             ->where('jurnal_details.chart_of_account_id', $account->id)
             ->where('jurnals.klinik_id', $klinikId) // Filter Klinik
             ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate]); // Filter Rentang

         $totalDebits = (clone $query)->sum('jurnal_details.debit');
         $totalKredits = $query->sum('jurnal_details.kredit');

         return ($account->saldo_normal == 'Debit')
                ? ($totalDebits - $totalKredits)
                // Pendapatan saldo normal kredit, Biaya saldo normal debit
                : ($totalKredits - $totalDebits);
     }

    /**
     * Menampilkan laporan Neraca.
     * Disesuaikan untuk filter klinik berdasarkan role.
     */
    /**
     * Menampilkan laporan Neraca.
     * Disesuaikan untuk filter klinik berdasarkan role.
     */
    public function neraca(Request $request)
    {
        $user = Auth::user();

        // Ambil klinik yang relevan
        $kliniks = collect();
        if ($user->hasRole('Superadmin')) {
            $kliniks = Klinik::where('is_active', true)->orderBy('nama_klinik', 'asc')->get();
        } elseif ($user->klinik_id) {
            $kliniks = Klinik::where('id', $user->klinik_id)->get();
        }

        // Tentukan Klinik ID
        $klinikIdFilter = null;
        if ($user->hasRole('Superadmin')) {
            $klinikIdFilter = $request->input('klinik_id');
        } else {
            $klinikIdFilter = $user->klinik_id;
             if (!$klinikIdFilter && !$user->hasRole('Superadmin')) {
                 // Kirim data kosong + error jika Admin/Staf tidak punya klinik
                 return view('laporan.neraca', [
                     'kliniks' => $kliniks, 'listAset' => [], 'totalAset' => 0,
                     'listLiabilitas' => [], 'totalLiabilitas' => 0, 'listEkuitas' => [], 'totalEkuitas' => 0,
                     'labaRugiBerjalan' => 0, 'totalLiabilitasDanEkuitas' => 0,
                     'endDate' => $request->input('end_date', date('Y-m-t')),
                     'klinikDipilih' => null, 'klinikIdFilter' => null,
                     'error' => 'Akun Anda belum terhubung ke klinik.'
                 ]);
             }
        }

        $endDate = $request->input('end_date', date('Y-m-t'));
        $klinikDipilih = is_numeric($klinikIdFilter) ? Klinik::find($klinikIdFilter) : null;
        $yearStartDate = Carbon::parse($endDate)->startOfYear()->format('Y-m-d');

        $listAset = []; $totalAset = 0;
        $listLiabilitas = []; $totalLiabilitas = 0;
        $listEkuitas = []; $totalEkuitas = 0;
        $labaRugiBerjalan = 0;
        $totalLiabilitasDanEkuitas = 0;

        // Hanya proses jika klinik sudah dipilih/ditentukan (harus numerik ID)
        if (is_numeric($klinikIdFilter)) {
            // --- A. KELOMPOK ASET (Global + Spesifik Klinik) ---
             $asetAccounts = ChartOfAccount::where('tipe_akun', 'Aset')
                                          ->where(function($q) use ($klinikIdFilter){
                                               $q->whereNull('klinik_id')
                                                 ->orWhere('klinik_id', $klinikIdFilter);
                                          })
                                          ->orderBy('kode_akun', 'asc')->get();
            foreach ($asetAccounts as $account) {
                // Gunakan fungsi helper hitungSaldoAkun (sudah diupdate)
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
                if (round($saldo, 2) != 0) { // Gunakan round
                    $listAset[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalAset += $saldo;
                }
            }

            // --- B. KELOMPOK LIABILITAS (Global + Spesifik Klinik) ---
             $liabilitasAccounts = ChartOfAccount::where('tipe_akun', 'Liabilitas')
                                                ->where(function($q) use ($klinikIdFilter){
                                                     $q->whereNull('klinik_id')
                                                       ->orWhere('klinik_id', $klinikIdFilter);
                                                })
                                                ->orderBy('kode_akun', 'asc')->get();
            foreach ($liabilitasAccounts as $account) {
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
                 if (round($saldo, 2) != 0) {
                    $listLiabilitas[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalLiabilitas += $saldo;
                }
            }

            // --- C. KELOMPOK EKUITAS (Global + Spesifik Klinik) ---
             $ekuitasAccounts = ChartOfAccount::where('tipe_akun', 'Ekuitas')
                                              ->where(function($q) use ($klinikIdFilter){
                                                   $q->whereNull('klinik_id')
                                                     ->orWhere('klinik_id', $klinikIdFilter);
                                              })
                                              ->orderBy('kode_akun', 'asc')->get();
            foreach ($ekuitasAccounts as $account) {
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
                 if (round($saldo, 2) != 0) {
                    $listEkuitas[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalEkuitas += $saldo;
                }
            }

            // --- D. LABA RUGI BERJALAN (Current Year's Net Income) ---
            // Gunakan fungsi helper hitungLabaRugi (sudah diupdate)
            $labaRugiBerjalan = $this->hitungLabaRugi($yearStartDate, $endDate, $klinikIdFilter);

            $totalEkuitas += $labaRugiBerjalan; // Tambahkan Laba Rugi ke Ekuitas
            $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitas;
        } // Akhir if(is_numeric($klinikIdFilter))

        return view('laporan.neraca', compact(
            'kliniks',
            'listAset', 'totalAset',
            'listLiabilitas', 'totalLiabilitas',
            'listEkuitas', 'totalEkuitas',
            'labaRugiBerjalan',
            'totalLiabilitasDanEkuitas',
            'endDate',
            'klinikDipilih', // Kirim model Klinik atau null
            'klinikIdFilter' // Kirim ID klinik filter
        ));
    }

    // --- Pastikan SEMUA FUNGSI HELPER ada di sini ---
    // private function hitungSaldoAkun($account, $endDate, $klinikId) { ... }
    // private function hitungMutasiAkun($account, $startDate, $endDate, $klinikId) { ... }
    // private function hitungLabaRugi($startDate, $endDate, $klinikId) { ... }

    /**
     * Fungsi helper untuk menghitung saldo akhir sebuah akun s/d tanggal tertentu
     * DITAMBAHKAN filter $klinikId.
     */
    private function hitungSaldoAkun($account, $endDate, $klinikId)
    {
        $query = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
            ->where('jurnal_details.chart_of_account_id', $account->id)
            ->where('jurnals.klinik_id', $klinikId) // <-- Filter Klinik
            ->where('jurnals.tanggal_transaksi', '<=', $endDate);

        $totalDebits = (clone $query)->sum('jurnal_details.debit');
        $totalKredits = $query->sum('jurnal_details.kredit');

        return ($account->saldo_normal == 'Debit')
               ? ($totalDebits - $totalKredits)
               : ($totalKredits - $totalDebits);
    }


    private function hitungLabaRugi($startDate, $endDate, $klinikId)
    {
        $totalPendapatan = 0;
        $pendapatanAccounts = ChartOfAccount::where('tipe_akun', 'Pendapatan')->get();
        foreach ($pendapatanAccounts as $account) {
            // Gunakan fungsi hitungMutasiAkun
            $totalPendapatan += $this->hitungMutasiAkun($account, $startDate, $endDate, $klinikId);
        }

        $totalBiaya = 0;
        $biayaAccounts = ChartOfAccount::where('tipe_akun', 'Biaya')->get();
        foreach ($biayaAccounts as $account) {
            // Gunakan fungsi hitungMutasiAkun
            $totalBiaya += $this->hitungMutasiAkun($account, $startDate, $endDate, $klinikId);
        }

        return $totalPendapatan - $totalBiaya;
    }
    private function hitungSaldoAwal($account, $startDate, $klinikId) // Pastikan nama SAMA PERSIS
    {
        // Tentukan ID klinik aktual (null jika 'global')
        $actualKlinikId = is_numeric($klinikId) ? (int)$klinikId : null;

        $saldoAwalDebits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
            ->where('jurnal_details.chart_of_account_id', $account->id)
            ->where('jurnals.klinik_id', $actualKlinikId) // Gunakan actualKlinikId
            ->where('jurnals.tanggal_transaksi', '<', $startDate) // Kurang dari startDate
            ->sum('jurnal_details.debit');

        $saldoAwalKredits = JurnalDetail::join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
            ->where('jurnal_details.chart_of_account_id', $account->id)
            ->where('jurnals.klinik_id', $actualKlinikId) // Gunakan actualKlinikId
            ->where('jurnals.tanggal_transaksi', '<', $startDate) // Kurang dari startDate
            ->sum('jurnal_details.kredit');

        return ($account->saldo_normal == 'Debit')
               ? ($saldoAwalDebits - $saldoAwalKredits)
               : ($saldoAwalKredits - $saldoAwalDebits);
    }

    /**
     * Fungsi helper untuk mengambil detail mutasi jurnal sebuah akun
     * PADA rentang tanggal tertentu dan klinik tertentu (null jika global).
     */
    private function ambilMutasi($account, $startDate, $endDate, $klinikId) // Pastikan nama SAMA PERSIS
    {
        // Tentukan ID klinik aktual (null jika 'global')
        $actualKlinikId = is_numeric($klinikId) ? (int)$klinikId : null;

        return JurnalDetail::with('jurnal')
            ->join('jurnals', 'jurnal_details.jurnal_id', '=', 'jurnals.id')
            ->where('jurnal_details.chart_of_account_id', $account->id)
            ->where('jurnals.klinik_id', $actualKlinikId) // Gunakan actualKlinikId
            ->whereBetween('jurnals.tanggal_transaksi', [$startDate, $endDate]) // Antara startDate dan endDate
            ->orderBy('jurnals.tanggal_transaksi', 'asc')
            ->orderBy('jurnals.id', 'asc')
            ->select('jurnal_details.*')
            ->get();
    }
    public function exportBukuBesar(Request $request)
{
    $user = Auth::user();

    // 1. Validasi Input Filter (sama seperti bukuBesar())
    $request->validate([
        'klinik_id' => ['required', function($attribute, $value, $fail) use ($user){
             if($value == 'global' && !$user->hasRole('Superadmin')) {
                  $fail('Anda tidak bisa ekspor akun global.');
             } elseif (!is_numeric($value) && $value != 'global') {
                  $fail('Klinik tidak valid.');
             } elseif (is_numeric($value) && !$user->hasRole('Superadmin') && $user->klinik_id != $value) {
                 $fail('Anda tidak bisa ekspor data klinik lain.');
             }
        }],
        'akun_id' => 'required|integer|exists:chart_of_accounts,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'type' => 'required|in:excel,pdf' // Tipe export (excel/pdf)
    ]);

    // 2. Ambil Data (Logika sama persis seperti di bukuBesar())
    $klinikIdFilter = $request->klinik_id;
    $akunIdFilter = $request->akun_id;
    $startDate = $request->start_date;
    $endDate = $request->end_date;

    $akunDipilih = ChartOfAccount::find($akunIdFilter);
    $klinikDipilih = null; // Bisa null jika global
    $actualKlinikId = null; // ID klinik untuk query

    if ($klinikIdFilter === 'global' && $akunDipilih && $akunDipilih->klinik_id === null) {
        $actualKlinikId = null; // Laporan global
    } elseif (is_numeric($klinikIdFilter)) {
        $klinikDipilih = Klinik::find($klinikIdFilter);
        if ($klinikDipilih && $akunDipilih && ($akunDipilih->klinik_id === null || $akunDipilih->klinik_id == $klinikIdFilter)) {
             $actualKlinikId = (int)$klinikIdFilter; // Laporan klinik spesifik
        } else {
             return back()->with('error', 'Kombinasi klinik dan akun tidak valid untuk diekspor.');
        }
    } else {
        return back()->with('error', 'Konteks klinik tidak valid untuk diekspor.');
    }

    // Hitung Saldo Awal & Ambil Mutasi (gunakan fungsi helper)
    $saldoAwal = $this->hitungSaldoAwal($akunDipilih, $startDate, $actualKlinikId);
    $data = $this->ambilMutasi($akunDipilih, $startDate, $endDate, $actualKlinikId);

    // 3. Siapkan Nama File
    $klinikName = $klinikDipilih ? $klinikDipilih->kode_klinik ?? str_replace(' ','_',$klinikDipilih->nama_klinik) : 'GLOBAL';
    $akunKode = $akunDipilih->kode_akun;
    $fileName = "BukuBesar_{$klinikName}_{$akunKode}_{$startDate}_sd_{$endDate}." . ($request->type == 'pdf' ? 'pdf' : 'xlsx');

    // 4. Lakukan Export
    if ($request->type == 'pdf') {
        // Untuk PDF, Laravel Excel perlu library tambahan (misal dompdf)
        // composer require dompdf/dompdf
        // Jika Anda belum install, export PDF akan error
         return Excel::download(new BukuBesarExport($data, $saldoAwal, $akunDipilih, $klinikDipilih, $startDate, $endDate), $fileName, \Maatwebsite\Excel\Excel::DOMPDF);
    } else {
        // Export Excel
         return Excel::download(new BukuBesarExport($data, $saldoAwal, $akunDipilih, $klinikDipilih, $startDate, $endDate), $fileName);
    }
}
public function exportNeracaSaldo(Request $request)
    {
        $user = Auth::user();

        // 1. Validasi Input Filter
        $request->validate([
            'klinik_id' => ['required', function($attribute, $value, $fail) use ($user){
                 if (!is_numeric($value)) { $fail('Klinik tidak valid.'); }
                 elseif (!$user->hasRole('Superadmin') && $user->klinik_id != $value) { $fail('Anda tidak bisa ekspor data klinik lain.'); }
            }],
            'end_date' => 'required|date',
            'type' => 'required|in:excel,pdf'
        ]);

        // 2. Ambil Data (Logika sama persis seperti neracaSaldo())
        $klinikIdFilter = $request->klinik_id;
        $endDate = $request->end_date;
        $klinikDipilih = Klinik::find($klinikIdFilter);

        if(!$klinikDipilih){ abort(404, 'Klinik tidak ditemukan'); } // Handle jika klinik tdk ada

        $laporanData = []; $totalDebit = 0; $totalKredit = 0;

        $accounts = ChartOfAccount::where(function($q) use ($klinikIdFilter){ $q->whereNull('klinik_id')->orWhere('klinik_id', $klinikIdFilter); })->orderBy('kode_akun')->get();

        foreach ($accounts as $account) {
            $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
            if (round($saldo, 2) != 0) {
                $saldoDebit = ($account->saldo_normal == 'Debit') ? $saldo : 0;
                $saldoKredit = ($account->saldo_normal == 'Kredit') ? $saldo : 0;
                $totalDebit += $saldoDebit; $totalKredit += $saldoKredit;
                $laporanData[] = [ // Buat array untuk export
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'debit' => $saldoDebit, // Kirim angka mentah
                    'kredit' => $saldoKredit, // Kirim angka mentah
                ];
            }
        }

        // 3. Siapkan Nama File
        $klinikName = $klinikDipilih->kode_klinik ?? str_replace(' ','_',$klinikDipilih->nama_klinik);
        $fileName = "NeracaSaldo_{$klinikName}_{$endDate}." . ($request->type == 'pdf' ? 'pdf' : 'xlsx');

        // 4. Lakukan Export
        $export = new NeracaSaldoExport($laporanData, $totalDebit, $totalKredit, $klinikDipilih, $endDate);
        if ($request->type == 'pdf') {
             // Pastikan dompdf terinstall: composer require dompdf/dompdf
             return Excel::download($export, $fileName, \Maatwebsite\Excel\Excel::DOMPDF);
        } else {
             return Excel::download($export, $fileName);
        }
    }
    public function exportLabaRugi(Request $request)
    {
        $user = Auth::user();

        // 1. Validasi Input Filter
        $request->validate([
            'klinik_id' => ['required', function($attribute, $value, $fail) use ($user){
                 if (!is_numeric($value)) { $fail('Klinik tidak valid.'); }
                 elseif (!$user->hasRole('Superadmin') && $user->klinik_id != $value) { $fail('Anda tidak bisa ekspor data klinik lain.'); }
            }],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:excel,pdf'
        ]);

        // 2. Ambil Data (Logika sama persis seperti labaRugi())
        $klinikIdFilter = $request->klinik_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $klinikDipilih = Klinik::find($klinikIdFilter);

        if(!$klinikDipilih){ abort(404, 'Klinik tidak ditemukan'); }

        $listPendapatan = []; $listBiaya = [];
        $totalPendapatan = 0; $totalBiaya = 0; $labaRugi = 0;

        // Hitung Pendapatan
        $pendapatanAccounts = ChartOfAccount::where('tipe_akun', 'Pendapatan')->where(function($q) use ($klinikIdFilter){ $q->whereNull('klinik_id')->orWhere('klinik_id', $klinikIdFilter); })->orderBy('kode_akun')->get();
        foreach ($pendapatanAccounts as $account) {
            $saldo = $this->hitungMutasiAkun($account, $startDate, $endDate, $klinikIdFilter);
            if ($saldo != 0) {
                $listPendapatan[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                $totalPendapatan += $saldo;
            }
        }
        // Hitung Biaya
        $biayaAccounts = ChartOfAccount::where('tipe_akun', 'Biaya')->where(function($q) use ($klinikIdFilter){ $q->whereNull('klinik_id')->orWhere('klinik_id', $klinikIdFilter); })->orderBy('kode_akun')->get();
        foreach ($biayaAccounts as $account) {
            $saldo = $this->hitungMutasiAkun($account, $startDate, $endDate, $klinikIdFilter);
            if ($saldo != 0) {
                $listBiaya[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                $totalBiaya += $saldo;
            }
        }
        $labaRugi = $totalPendapatan - $totalBiaya;

        // 3. Siapkan Nama File
        $klinikName = $klinikDipilih->kode_klinik ?? str_replace(' ','_',$klinikDipilih->nama_klinik);
        $fileName = "LabaRugi_{$klinikName}_{$startDate}_sd_{$endDate}." . ($request->type == 'pdf' ? 'pdf' : 'xlsx');

        // 4. Lakukan Export
        $export = new LabaRugiExport($listPendapatan, $listBiaya, $totalPendapatan, $totalBiaya, $labaRugi, $klinikDipilih, $startDate, $endDate);
        if ($request->type == 'pdf') {
             return Excel::download($export, $fileName, \Maatwebsite\Excel\Excel::DOMPDF);
        } else {
             return Excel::download($export, $fileName);
        }
    }
    public function exportNeraca(Request $request)
    {
        $user = Auth::user();

        // 1. Validasi Input Filter
        $request->validate([
            'klinik_id' => ['required', function($attribute, $value, $fail) use ($user){
                 if (!is_numeric($value)) { $fail('Klinik tidak valid.'); }
                 elseif (!$user->hasRole('Superadmin') && $user->klinik_id != $value) { $fail('Anda tidak bisa ekspor data klinik lain.'); }
            }],
            'end_date' => 'required|date',
            'type' => 'required|in:excel,pdf'
        ]);

        // 2. Ambil Data (Logika sama persis seperti neraca())
        $klinikIdFilter = $request->klinik_id;
        $endDate = $request->end_date;
        $klinikDipilih = Klinik::find($klinikIdFilter);
        $yearStartDate = Carbon::parse($endDate)->startOfYear()->format('Y-m-d');

        if(!$klinikDipilih){ abort(404, 'Klinik tidak ditemukan'); }

        $listAset = []; $totalAset = 0;
        $listLiabilitas = []; $totalLiabilitas = 0;
        $listEkuitas = []; $totalEkuitasAwal = 0; // Ekuitas sebelum laba rugi
        $labaRugiBerjalan = 0; $totalLiabilitasDanEkuitas = 0; $totalEkuitasAkhir = 0;

        // Aset
        $asetAccounts = ChartOfAccount::where('tipe_akun', 'Aset')->where(function($q) use ($klinikIdFilter){ $q->whereNull('klinik_id')->orWhere('klinik_id', $klinikIdFilter); })->orderBy('kode_akun')->get();
        foreach ($asetAccounts as $account) {
            $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
            if (round($saldo, 2) != 0) {
                $listAset[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                $totalAset += $saldo;
            }
        }
        // Liabilitas
        $liabilitasAccounts = ChartOfAccount::where('tipe_akun', 'Liabilitas')->where(function($q) use ($klinikIdFilter){ $q->whereNull('klinik_id')->orWhere('klinik_id', $klinikIdFilter); })->orderBy('kode_akun')->get();
        foreach ($liabilitasAccounts as $account) {
            $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
             if (round($saldo, 2) != 0) {
                $listLiabilitas[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                $totalLiabilitas += $saldo;
            }
        }
        // Ekuitas Awal
        $ekuitasAccounts = ChartOfAccount::where('tipe_akun', 'Ekuitas')->where(function($q) use ($klinikIdFilter){ $q->whereNull('klinik_id')->orWhere('klinik_id', $klinikIdFilter); })->orderBy('kode_akun')->get();
        foreach ($ekuitasAccounts as $account) {
            $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
             if (round($saldo, 2) != 0) {
                $listEkuitas[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                $totalEkuitasAwal += $saldo;
            }
        }
        // Laba Rugi Berjalan
        $labaRugiBerjalan = $this->hitungLabaRugi($yearStartDate, $endDate, $klinikIdFilter);
        $totalEkuitasAkhir = $totalEkuitasAwal + $labaRugiBerjalan;
        $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitasAkhir;

        // 3. Siapkan Nama File
        $klinikName = $klinikDipilih->kode_klinik ?? str_replace(' ','_',$klinikDipilih->nama_klinik);
        $fileName = "Neraca_{$klinikName}_{$endDate}." . ($request->type == 'pdf' ? 'pdf' : 'xlsx');

        // 4. Lakukan Export
        $export = new NeracaExport(
             $listAset, $totalAset,
             $listLiabilitas, $totalLiabilitas,
             $listEkuitas, $totalEkuitasAkhir, // Kirim total ekuitas AKHIR
             $labaRugiBerjalan, // Kirim laba rugi terpisah
             $totalLiabilitasDanEkuitas,
             $klinikDipilih, $endDate
        );
        if ($request->type == 'pdf') {
             return Excel::download($export, $fileName, \Maatwebsite\Excel\Excel::DOMPDF);
        } else {
             return Excel::download($export, $fileName);
        }
    }

}