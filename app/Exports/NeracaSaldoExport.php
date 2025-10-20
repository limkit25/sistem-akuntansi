<?php

namespace App\Exports;

use App\Models\Klinik;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray; // Gunakan FromArray karena data sudah diproses
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Untuk format angka
use Maatwebsite\Excel\Concerns\WithColumnFormatting; // Untuk format angka

class NeracaSaldoExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    protected $laporanData;
    protected $totalDebit;
    protected $totalKredit;
    protected $klinikDipilih;
    protected $endDate;

    public function __construct($laporanData, $totalDebit, $totalKredit, $klinikDipilih, $endDate)
    {
        $this->laporanData = $laporanData;
        $this->totalDebit = $totalDebit;
        $this->totalKredit = $totalKredit;
        $this->klinikDipilih = $klinikDipilih;
        $this->endDate = $endDate;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        // Data sudah dalam format array yang benar dari controller
        return $this->laporanData;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $klinikName = $this->klinikDipilih ? $this->klinikDipilih->nama_klinik : 'Keseluruhan (jika diimplementasikan)';
        return [
            ['Neraca Saldo'],
            ['Klinik: ' . $klinikName],
            ['Per Tanggal: ' . Carbon::parse($this->endDate)->format('d M Y')],
            [], // Baris kosong
            [ // Header tabel
                'Kode Akun',
                'Nama Akun',
                'Debit',
                'Kredit'
            ]
        ];
    }

     /**
     * @return array
     */
    // Format kolom C (Debit) dan D (Kredit) sebagai angka
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Style Header
                $event->sheet->getDelegate()->getStyle('A1:D3')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A5:D5')->getFont()->setBold(true);

                // Tambahkan baris Total
                $lastRow = $event->sheet->getHighestRow() + 1;
                $event->sheet->setCellValue("A{$lastRow}", 'TOTAL');
                $event->sheet->mergeCells("A{$lastRow}:B{$lastRow}");
                $event->sheet->setCellValue("C{$lastRow}", $this->totalDebit);
                $event->sheet->setCellValue("D{$lastRow}", $this->totalKredit);

                // Style Total
                $event->sheet->getDelegate()->getStyle("A{$lastRow}:D{$lastRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 // Format angka total
                 $event->sheet->getDelegate()->getStyle("C{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                 $event->sheet->getDelegate()->getStyle("D{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                 // Rata kanan total
                 $event->sheet->getDelegate()->getStyle("C{$lastRow}:D{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);


                // Tambahkan baris Balance/Selisih (jika perlu)
                $balanceRow = $lastRow + 1;
                if(round($this->totalDebit, 2) == round($this->totalKredit, 2)){
                    $event->sheet->setCellValue("A{$balanceRow}", 'BALANCE');
                    $event->sheet->mergeCells("A{$balanceRow}:D{$balanceRow}");
                     $event->sheet->getDelegate()->getStyle("A{$balanceRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                } else {
                    $event->sheet->setCellValue("A{$balanceRow}", 'SELISIH');
                     $event->sheet->mergeCells("A{$balanceRow}:B{$balanceRow}");
                    $event->sheet->setCellValue("C{$balanceRow}", $this->totalDebit - $this->totalKredit);
                     $event->sheet->mergeCells("C{$balanceRow}:D{$balanceRow}"); // Gabung C & D
                     $event->sheet->getDelegate()->getStyle("A{$balanceRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                     $event->sheet->getDelegate()->getStyle("C{$balanceRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                      $event->sheet->getDelegate()->getStyle("C{$balanceRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }
                 $event->sheet->getDelegate()->getStyle("A{$balanceRow}:D{$balanceRow}")->getFont()->setBold(true);

            },
        ];
    }


    /**
     * @return string
     */
    public function title(): string
    {
        return 'Neraca Saldo';
    }
}