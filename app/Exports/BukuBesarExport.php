<?php

namespace App\Exports;

use App\Models\JurnalDetail;
use App\Models\ChartOfAccount;
use App\Models\Klinik;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents; // Untuk styling
use Maatwebsite\Excel\Events\AfterSheet; // Untuk styling

class BukuBesarExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected $data;
    protected $saldoAwal;
    protected $akunDipilih;
    protected $klinikDipilih;
    protected $startDate;
    protected $endDate;
    protected $saldo; // Untuk running balance

    public function __construct($data, $saldoAwal, $akunDipilih, $klinikDipilih, $startDate, $endDate)
    {
        $this->data = $data;
        $this->saldoAwal = $saldoAwal;
        $this->akunDipilih = $akunDipilih;
        $this->klinikDipilih = $klinikDipilih;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->saldo = $saldoAwal; // Mulai saldo dari saldo awal
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Kita hanya perlu datanya saja, karena header dan saldo awal/akhir diatur terpisah
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Judul utama laporan (digabungkan)
        $klinikName = $this->klinikDipilih ? $this->klinikDipilih->nama_klinik : 'Global';
        $reportTitle = "Buku Besar: [{$this->akunDipilih->kode_akun}] {$this->akunDipilih->nama_akun}";
        $clinicTitle = "Klinik: {$klinikName}";
        $periodTitle = "Periode: " . Carbon::parse($this->startDate)->format('d M Y') . " s/d " . Carbon::parse($this->endDate)->format('d M Y');

        return [
            // Baris 1: Judul Laporan
            [$reportTitle],
            // Baris 2: Nama Klinik
            [$clinicTitle],
            // Baris 3: Periode
            [$periodTitle],
            // Baris 4: Kosong
            [],
            // Baris 5: Header Tabel Aktual
            [
                'Tanggal',
                'Deskripsi',
                'Debit',
                'Kredit',
                'Saldo'
            ],
            // Baris 6: Saldo Awal (Data dummy, akan diisi di map())
             [
                'Saldo Awal',
                '',
                '',
                '',
                number_format($this->saldoAwal, 2, ',', '.') // Format saldo awal
             ]
        ];
    }

    /**
     * @param JurnalDetail $detail
     * @return array
     */
    public function map($detail): array
    {
        // Hitung Saldo Berjalan (Running Balance)
        if ($this->akunDipilih->saldo_normal == 'Debit') {
            $this->saldo += $detail->debit - $detail->kredit;
        } else { // Saldo normal 'Kredit'
            $this->saldo += $detail->kredit - $detail->debit;
        }

        return [
            Carbon::parse($detail->jurnal->tanggal_transaksi)->format('d-m-Y'),
            $detail->jurnal->deskripsi,
            number_format($detail->debit, 2, ',', '.'),  // Format angka
            number_format($detail->kredit, 2, ',', '.'), // Format angka
            number_format($this->saldo, 2, ',', '.')     // Format angka
        ];
    }

     /**
     * @return array
     */
     // Fungsi ini menambahkan baris Saldo Akhir di paling bawah
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Style Header (Opsional)
                $event->sheet->getDelegate()->getStyle('A1:E3')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A5:E6')->getFont()->setBold(true);
                 $event->sheet->getDelegate()->getStyle('E6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); //Saldo Awal rata kanan


                // Tambahkan baris Saldo Akhir
                $lastRow = $event->sheet->getHighestRow() + 1; // Baris setelah data terakhir
                $event->sheet->setCellValue("A{$lastRow}", 'Saldo Akhir');
                $event->sheet->mergeCells("A{$lastRow}:D{$lastRow}"); // Gabungkan sel A-D
                $event->sheet->setCellValue("E{$lastRow}", number_format($this->saldo, 2, ',', '.')); // Tulis saldo akhir

                // Styling Saldo Akhir (Opsional)
                $event->sheet->getDelegate()->getStyle("A{$lastRow}:E{$lastRow}")->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                 $event->sheet->getDelegate()->getStyle("E{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        // Nama sheet di file Excel
        return 'Buku Besar ' . $this->akunDipilih->kode_akun;
    }
}