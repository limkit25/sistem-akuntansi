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
    public function neracaSaldo(Request $request)
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
            $klinikIdFilter = $request->input('klinik_id');
        } else {
            $klinikIdFilter = $user->klinik_id;
            // Jika Admin/Staf tidak punya klinik, tampilkan error
            if (!$klinikIdFilter) {
                return view('laporan.neraca-saldo', [
                    'kliniks' => $kliniks,
                    'laporanData' => [],
                    'totalDebit' => 0, 'totalKredit' => 0,
                    'endDate' => $request->input('end_date', date('Y-m-t')),
                    'klinikDipilih' => null, 'klinikIdFilter' => null,
                    'error' => 'Akun Anda belum terhubung ke klinik.'
                ]);
            }
        }

        $endDate = $request->input('end_date', date('Y-m-t'));
        $klinikDipilih = $klinikIdFilter ? Klinik::find($klinikIdFilter) : null;

        $laporanData = [];
        $totalDebit = 0;
        $totalKredit = 0;

        // Hanya proses jika klinik sudah dipilih/ditentukan
        if ($klinikIdFilter) {
            // Ambil akun global DAN akun spesifik klinik yang dipilih
            $accounts = ChartOfAccount::whereNull('klinik_id')
                            ->orWhere('klinik_id', $klinikIdFilter)
                            ->orderBy('kode_akun', 'asc')
                            ->get();

            foreach ($accounts as $account) {
                // Hitung saldo HANYA untuk klinik yang dipilih
                // (Fungsi hitungSaldoAkun sudah diupdate sebelumnya untuk menerima klinik_id)
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);

                // Tampilkan hanya jika saldo tidak nol
                if (round($saldo, 2) != 0) { // Gunakan round untuk presisi
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
        }

        return view('laporan.neraca-saldo', compact(
            'kliniks',
            'laporanData',
            'totalDebit',
            'totalKredit',
            'endDate',
            'klinikDipilih',
            'klinikIdFilter' // Kirim untuk selected filter
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
             if (!$klinikIdFilter) {
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
        $klinikDipilih = $klinikIdFilter ? Klinik::find($klinikIdFilter) : null;

        $listPendapatan = [];
        $listBiaya = [];
        $totalPendapatan = 0;
        $totalBiaya = 0;
        $labaRugi = 0;

        // Hanya proses jika klinik sudah dipilih/ditentukan
        if ($klinikIdFilter) {
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
        }

        return view('laporan.laba-rugi', compact(
            'kliniks',
            'listPendapatan', 'listBiaya',
            'totalPendapatan', 'totalBiaya',
            'labaRugi',
            'startDate', 'endDate',
            'klinikDipilih', 'klinikIdFilter' // Kirim untuk view
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
             if (!$klinikIdFilter) {
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
        $klinikDipilih = $klinikIdFilter ? Klinik::find($klinikIdFilter) : null;
        $yearStartDate = Carbon::parse($endDate)->startOfYear()->format('Y-m-d');

        $listAset = []; $totalAset = 0;
        $listLiabilitas = []; $totalLiabilitas = 0;
        $listEkuitas = []; $totalEkuitas = 0;
        $labaRugiBerjalan = 0;
        $totalLiabilitasDanEkuitas = 0;

        // Hanya proses jika klinik sudah dipilih/ditentukan
        if ($klinikIdFilter) {
            // Aset (Global + Spesifik Klinik)
             $asetAccounts = ChartOfAccount::where('tipe_akun', 'Aset')
                                          ->where(function($q) use ($klinikIdFilter){
                                               $q->whereNull('klinik_id')
                                                 ->orWhere('klinik_id', $klinikIdFilter);
                                          })
                                          ->orderBy('kode_akun', 'asc')->get();
            foreach ($asetAccounts as $account) {
                // Gunakan fungsi helper hitungSaldoAkun (sudah diupdate)
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
                if ($saldo != 0) {
                    $listAset[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalAset += $saldo;
                }
            }

            // Liabilitas (Global + Spesifik Klinik)
             $liabilitasAccounts = ChartOfAccount::where('tipe_akun', 'Liabilitas')
                                                ->where(function($q) use ($klinikIdFilter){
                                                     $q->whereNull('klinik_id')
                                                       ->orWhere('klinik_id', $klinikIdFilter);
                                                })
                                                ->orderBy('kode_akun', 'asc')->get();
            foreach ($liabilitasAccounts as $account) {
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
                if ($saldo != 0) {
                    $listLiabilitas[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalLiabilitas += $saldo;
                }
            }

            // Ekuitas (Global + Spesifik Klinik)
             $ekuitasAccounts = ChartOfAccount::where('tipe_akun', 'Ekuitas')
                                              ->where(function($q) use ($klinikIdFilter){
                                                   $q->whereNull('klinik_id')
                                                     ->orWhere('klinik_id', $klinikIdFilter);
                                              })
                                              ->orderBy('kode_akun', 'asc')->get();
            foreach ($ekuitasAccounts as $account) {
                $saldo = $this->hitungSaldoAkun($account, $endDate, $klinikIdFilter);
                if ($saldo != 0) {
                    $listEkuitas[] = ['kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun, 'total' => $saldo];
                    $totalEkuitas += $saldo;
                }
            }

            // Laba Rugi Berjalan (dari awal tahun s/d end date, untuk klinik ini)
            // Gunakan fungsi helper hitungLabaRugi (sudah diupdate)
            $labaRugiBerjalan = $this->hitungLabaRugi($yearStartDate, $endDate, $klinikIdFilter);

            $totalEkuitas += $labaRugiBerjalan; // Tambahkan Laba Rugi Berjalan ke Ekuitas
            $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitas;
        }

        return view('laporan.neraca', compact(
            'kliniks',
            'listAset', 'totalAset',
            'listLiabilitas', 'totalLiabilitas',
            'listEkuitas', 'totalEkuitas',
            'labaRugiBerjalan',
            'totalLiabilitasDanEkuitas',
            'endDate',
            'klinikDipilih', 'klinikIdFilter' // Kirim untuk view
        ));
    }

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

}