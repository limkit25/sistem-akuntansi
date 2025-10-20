<?php

namespace App\Exports;

use App\Models\Klinik;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class NeracaExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    protected $listAset;
    protected $totalAset;
    protected $listLiabilitas;
    protected $totalLiabilitas;
    protected $listEkuitas;
    protected $totalEkuitas; // Ini sudah termasuk Laba Rugi Berjalan
    protected $labaRugiBerjalan;
    protected $totalLiabilitasDanEkuitas;
    protected $klinikDipilih;
    protected $endDate;

    public function __construct(
        $listAset, $totalAset,
        $listLiabilitas, $totalLiabilitas,
        $listEkuitas, $totalEkuitas, // Kirim total ekuitas akhir
        $labaRugiBerjalan, // Kirim laba rugi terpisah untuk ditampilkan
        $totalLiabilitasDanEkuitas,
        $klinikDipilih, $endDate
    )
    {
        $this->listAset = $listAset;
        $this->totalAset = $totalAset;
        $this->listLiabilitas = $listLiabilitas;
        $this->totalLiabilitas = $totalLiabilitas; // Total liabilitas saja
        $this->listEkuitas = $listEkuitas; // Daftar ekuitas awal
        $this->totalEkuitas = $totalEkuitas; // Total ekuitas akhir (termasuk laba rugi)
        $this->labaRugiBerjalan = $labaRugiBerjalan;
        $this->totalLiabilitasDanEkuitas = $totalLiabilitasDanEkuitas;
        $this->klinikDipilih = $klinikDipilih;
        $this->endDate = $endDate;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];

        // --- ASET ---
        $data[] = ['ASET', '']; // Header Grup
        if (empty($this->listAset)) {
            $data[] = ['  Tidak ada data Aset.', ''];
        } else {
            foreach ($this->listAset as $item) {
                 $isGlobal = \App\Models\ChartOfAccount::where('kode_akun', $item['kode_akun'])->whereNull('klinik_id')->exists();
                 $namaAkun = ($isGlobal ? '[G] ' : '  ') . '[' . $item['kode_akun'] . '] ' . $item['nama_akun'];
                $data[] = [$namaAkun, $item['total']];
            }
        }
        $data[] = ['TOTAL ASET', $this->totalAset];

        // --- PEMISAH ---
        $data[] = ['', ''];

        // --- LIABILITAS ---
        $data[] = ['LIABILITAS (KEWAJIBAN)', ''];
        if (empty($this->listLiabilitas)) {
            $data[] = ['  Tidak ada data Liabilitas.', ''];
        } else {
            foreach ($this->listLiabilitas as $item) {
                 $isGlobal = \App\Models\ChartOfAccount::where('kode_akun', $item['kode_akun'])->whereNull('klinik_id')->exists();
                 $namaAkun = ($isGlobal ? '[G] ' : '  ') . '[' . $item['kode_akun'] . '] ' . $item['nama_akun'];
                $data[] = [$namaAkun, $item['total']];
            }
        }
        $data[] = ['Total Liabilitas', $this->totalLiabilitas];

        // --- EKUITAS ---
        $data[] = ['EKUITAS (MODAL)', ''];
        if (empty($this->listEkuitas)) {
            $data[] = ['  Tidak ada data Ekuitas Awal.', ''];
        } else {
            foreach ($this->listEkuitas as $item) {
                 $isGlobal = \App\Models\ChartOfAccount::where('kode_akun', $item['kode_akun'])->whereNull('klinik_id')->exists();
                 $namaAkun = ($isGlobal ? '[G] ' : '  ') . '[' . $item['kode_akun'] . '] ' . $item['nama_akun'];
                $data[] = [$namaAkun, $item['total']];
            }
        }
        // Laba Rugi Berjalan
        $data[] = ['  Laba Rugi Tahun Berjalan', $this->labaRugiBerjalan];
        // Total Ekuitas (Sudah termasuk Laba Rugi)
        $data[] = ['Total Ekuitas', $this->totalEkuitas];


        return $data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $klinikName = $this->klinikDipilih ? $this->klinikDipilih->nama_klinik : '-';
        return [
            ['Laporan Neraca'],
            ['Klinik: ' . $klinikName],
            ['Per Tanggal: ' . Carbon::parse($this->endDate)->format('d M Y')],
            [], // Baris kosong
            [ // Header tabel
                'Deskripsi Akun',
                'Total'
            ]
        ];
    }

    /**
     * @return array
     */
    // Format kolom B (Total) sebagai angka
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Style Header Laporan & Tabel
                $event->sheet->getDelegate()->getStyle('A1:B3')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A5:B5')->getFont()->setBold(true);

                // --- Style Grup & Total ---
                // Aset
                $asetHeaderRow = 6;
                $totalAsetRow = $asetHeaderRow + (empty($this->listAset) ? 1 : count($this->listAset)) + 1;
                $event->sheet->getDelegate()->getStyle("A{$asetHeaderRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$totalAsetRow}:B{$totalAsetRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$totalAsetRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 $event->sheet->getDelegate()->getStyle("B{$totalAsetRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                // Liabilitas
                $liabilitasHeaderRow = $totalAsetRow + 2; // Setelah baris kosong
                $totalLiabilitasRow = $liabilitasHeaderRow + (empty($this->listLiabilitas) ? 1 : count($this->listLiabilitas)) + 1;
                $event->sheet->getDelegate()->getStyle("A{$liabilitasHeaderRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$totalLiabilitasRow}:B{$totalLiabilitasRow}")->getFont()->setBold(true);
                 $event->sheet->getDelegate()->getStyle("A{$totalLiabilitasRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 $event->sheet->getDelegate()->getStyle("B{$totalLiabilitasRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                // Ekuitas
                $ekuitasHeaderRow = $totalLiabilitasRow + 1;
                $labaRugiRow = $ekuitasHeaderRow + (empty($this->listEkuitas) ? 1 : count($this->listEkuitas)) + 1;
                $totalEkuitasRow = $labaRugiRow + 1;
                $event->sheet->getDelegate()->getStyle("A{$ekuitasHeaderRow}")->getFont()->setBold(true);
                 $event->sheet->getDelegate()->getStyle("A{$labaRugiRow}")->getFont()->setBold(true); // Laba Rugi Row
                 $event->sheet->getDelegate()->getStyle("A{$totalEkuitasRow}:B{$totalEkuitasRow}")->getFont()->setBold(true);
                 $event->sheet->getDelegate()->getStyle("A{$totalEkuitasRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 $event->sheet->getDelegate()->getStyle("B{$labaRugiRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); // Laba Rugi Value
                 $event->sheet->getDelegate()->getStyle("B{$totalEkuitasRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); // Total Ekuitas Value


                // Tambahkan baris Total Liabilitas + Ekuitas
                $totalLiabEkuitasRow = $totalEkuitasRow + 1;
                $event->sheet->setCellValue("A{$totalLiabEkuitasRow}", 'TOTAL LIABILITAS DAN EKUITAS');
                $event->sheet->setCellValue("B{$totalLiabEkuitasRow}", $this->totalLiabilitasDanEkuitas);

                 // Style Total Liabilitas + Ekuitas
                 $event->sheet->getDelegate()->getStyle("A{$totalLiabEkuitasRow}:B{$totalLiabEkuitasRow}")->getFont()->setBold(true);
                 $event->sheet->getDelegate()->getStyle("A{$totalLiabEkuitasRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 $event->sheet->getDelegate()->getStyle("B{$totalLiabEkuitasRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                 $event->sheet->getDelegate()->getStyle("B{$totalLiabEkuitasRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                // Tambahkan baris Balance/Selisih
                $balanceRow = $totalLiabEkuitasRow + 1;
                if(round($this->totalAset, 2) == round($this->totalLiabilitasDanEkuitas, 2)){
                    $event->sheet->setCellValue("A{$balanceRow}", 'BALANCE');
                    $event->sheet->mergeCells("A{$balanceRow}:B{$balanceRow}");
                     $event->sheet->getDelegate()->getStyle("A{$balanceRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                } else {
                    $event->sheet->setCellValue("A{$balanceRow}", 'SELISIH');
                    $event->sheet->setCellValue("B{$balanceRow}", $this->totalAset - $this->totalLiabilitasDanEkuitas);
                     $event->sheet->getDelegate()->getStyle("A{$balanceRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                     $event->sheet->getDelegate()->getStyle("B{$balanceRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                     $event->sheet->getDelegate()->getStyle("B{$balanceRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                $event->sheet->getDelegate()->getStyle("A{$balanceRow}:B{$balanceRow}")->getFont()->setBold(true);

            },
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Neraca';
    }
}