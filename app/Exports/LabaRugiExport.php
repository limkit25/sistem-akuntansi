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

class LabaRugiExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    protected $listPendapatan;
    protected $listBiaya;
    protected $totalPendapatan;
    protected $totalBiaya;
    protected $labaRugi;
    protected $klinikDipilih;
    protected $startDate;
    protected $endDate;

    public function __construct($listPendapatan, $listBiaya, $totalPendapatan, $totalBiaya, $labaRugi, $klinikDipilih, $startDate, $endDate)
    {
        $this->listPendapatan = $listPendapatan;
        $this->listBiaya = $listBiaya;
        $this->totalPendapatan = $totalPendapatan;
        $this->totalBiaya = $totalBiaya;
        $this->labaRugi = $labaRugi;
        $this->klinikDipilih = $klinikDipilih;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];

        // Header Pendapatan
        $data[] = ['Pendapatan', '']; // Baris header grup

        // List Pendapatan
        if (empty($this->listPendapatan)) {
            $data[] = ['  Tidak ada data pendapatan.', '']; // Indentasi
        } else {
            foreach ($this->listPendapatan as $item) {
                 $isGlobal = \App\Models\ChartOfAccount::where('kode_akun', $item['kode_akun'])->whereNull('klinik_id')->exists();
                 $namaAkun = ($isGlobal ? '[G] ' : '  ') . '[' . $item['kode_akun'] . '] ' . $item['nama_akun']; // Indentasi + prefix
                $data[] = [$namaAkun, $item['total']];
            }
        }
        // Total Pendapatan
        $data[] = ['Total Pendapatan', $this->totalPendapatan];

        // Baris Kosong Pemisah
        $data[] = ['', ''];

        // Header Biaya
        $data[] = ['Biaya', ''];

        // List Biaya
        if (empty($this->listBiaya)) {
            $data[] = ['  Tidak ada data biaya.', ''];
        } else {
            foreach ($this->listBiaya as $item) {
                 $isGlobal = \App\Models\ChartOfAccount::where('kode_akun', $item['kode_akun'])->whereNull('klinik_id')->exists();
                 $namaAkun = ($isGlobal ? '[G] ' : '  ') . '[' . $item['kode_akun'] . '] ' . $item['nama_akun'];
                $data[] = [$namaAkun, $item['total']];
            }
        }
        // Total Biaya
        $data[] = ['Total Biaya', $this->totalBiaya];

        return $data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $klinikName = $this->klinikDipilih ? $this->klinikDipilih->nama_klinik : '-';
        return [
            ['Laporan Laba Rugi'],
            ['Klinik: ' . $klinikName],
            ['Periode: ' . Carbon::parse($this->startDate)->format('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->format('d M Y')],
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
                // Style Header Laporan
                $event->sheet->getDelegate()->getStyle('A1:B3')->getFont()->setBold(true);
                // Style Header Tabel
                $event->sheet->getDelegate()->getStyle('A5:B5')->getFont()->setBold(true);

                // Style Header Grup (Pendapatan & Biaya)
                 $event->sheet->getDelegate()->getStyle('A6')->getFont()->setBold(true); // Pendapatan
                 $event->sheet->getDelegate()->getStyle('A'.(7 + count($this->listPendapatan) + 1))->getFont()->setBold(true); // Biaya (setelah baris kosong)


                // Style Total Pendapatan & Biaya
                $totalPendapatanRow = 6 + (empty($this->listPendapatan) ? 1 : count($this->listPendapatan)) + 1;
                $totalBiayaRow = $totalPendapatanRow + 2 + (empty($this->listBiaya) ? 1 : count($this->listBiaya)) + 1;

                $event->sheet->getDelegate()->getStyle("A{$totalPendapatanRow}:B{$totalPendapatanRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$totalPendapatanRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 $event->sheet->getDelegate()->getStyle("B{$totalPendapatanRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                $event->sheet->getDelegate()->getStyle("A{$totalBiayaRow}:B{$totalBiayaRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$totalBiayaRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 $event->sheet->getDelegate()->getStyle("B{$totalBiayaRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                // Tambahkan baris Laba Rugi Bersih
                $labaRugiRow = $totalBiayaRow + 1;
                $labaRugiLabel = $this->labaRugi >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH';
                $event->sheet->setCellValue("A{$labaRugiRow}", $labaRugiLabel);
                $event->sheet->setCellValue("B{$labaRugiRow}", $this->labaRugi);

                // Style Laba Rugi
                $event->sheet->getDelegate()->getStyle("A{$labaRugiRow}:B{$labaRugiRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$labaRugiRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getDelegate()->getStyle("B{$labaRugiRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                 $event->sheet->getDelegate()->getStyle("B{$labaRugiRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Laba Rugi';
    }
}