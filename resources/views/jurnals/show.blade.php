@extends('layouts.admin')

{{-- Judul halaman dinamis sesuai periode --}}
@section('title', 'Jurnal Umum - ' . $periodName)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('jurnals.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Jurnal Baru
                </a>
                <a href="{{ route('jurnals.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Periode
                </a>
            </div>
            <div class="card-body">
                @if($jurnals->isEmpty())
                    <div class="alert alert-info">
                        Belum ada data jurnal untuk periode {{ $periodName }}.
                    </div>
                @else
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Tanggal</th>
                                <th style="width: 15%;">No. Bukti</th>
                                <th>Deskripsi</th>
                                <th>Akun</th>
                                <th style="width: 12%;">Debit</th>
                                <th style="width: 12%;">Kredit</th>
                                <th style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Loop pertama: Per Transaksi (Induk Jurnal) --}}
                            @foreach ($jurnals as $jurnal)
                                {{-- Hitung jumlah baris yang dibutuhkan oleh detail --}}
                                @php $rowspan = $jurnal->details->count(); @endphp
                                
                                {{-- Loop kedua: Per Detail (Debit/Kredit) --}}
                                @foreach ($jurnal->details as $index => $detail)
                                    <tr>
                                        {{-- Kolom Tanggal, Bukti, Deskripsi hanya muncul di baris pertama --}}
                                        @if ($index === 0)
                                            <td rowspan="{{ $rowspan }}">{{ \Carbon\Carbon::parse($jurnal->tanggal_transaksi)->format('d-m-Y') }}</td>
                                            <td rowspan="{{ $rowspan }}">{{ $jurnal->nomor_bukti ?? '-' }}</td>
                                            <td rowspan="{{ $rowspan }}">{{ $jurnal->deskripsi }}</td>
                                        @endif
                                        
                                        {{-- Kolom Akun, Debit, Kredit (muncul di setiap baris detail) --}}
                                        <td>
                                            {{-- Tampilkan akun dengan indentasi jika itu baris kredit --}}
                                            <span style="padding-left: {{ $detail->kredit > 0 ? '20px' : '0' }};">
                                                [{{ $detail->account->kode_akun }}] {{ $detail->account->nama_akun }}
                                            </span>
                                        </td>
                                        <td class="text-right">{{ number_format($detail->debit, 2, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($detail->kredit, 2, ',', '.') }}</td>

                                        {{-- Kolom Aksi (Edit/Hapus) hanya muncul di baris pertama --}}
                                        @if ($index === 0)
                                            <td rowspan="{{ $rowspan }}" class="text-center">
                                                {{-- Kita akan fungsikan tombol ini nanti --}}
                                                <a href="{{ route('jurnals.edit', $jurnal->id) }}" class="btn btn-xs btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('jurnals.destroy', $jurnal->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-danger btn-delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th colspan="4" class="text-right">TOTAL</th>
                                <th class="text-right">{{ number_format($totalDebit, 2, ',', '.') }}</th>
                                <th class="text-right">{{ number_format($totalKredit, 2, ',', '.') }}</th>
                                <th></th>
                            </tr>
                            {{-- Cek Balance --}}
                            @if($totalDebit == $totalKredit)
                            <tr class="bg-success-light">
                                <th colspan="7" class="text-center">
                                    <i class="fas fa-check-circle"></i> BALANCE
                                </h3>
                            </tr>
                            @else
                            <tr class="bg-danger-light">
                                <th colspan="4" class="text-right">SELISIH</th>
                                <th colspan="2" class="text-center">{{ number_format($totalDebit - $totalKredit, 2, ',', '.') }}</th>
                                <th></th>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                @endif
            </div>
            </div>
        </div>
</div>
@endsection
@push('js')
<script>
    $(function() {
        // Menangani klik pada tombol dengan class .btn-delete
        $('.btn-delete').on('click', function(e) {
            
            // Mencegah form terkirim langsung
            e.preventDefault(); 
            
            // Simpan referensi ke form
            var form = $(this).closest('form'); 

            // Tampilkan pop up SweetAlert
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data jurnal ini akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Jika pengguna menekan "Ya, hapus!"
                if (result.isConfirmed) {
                    // Kirim form-nya
                    form.submit();
                }
            });
        });
    });
</script>
@endpush