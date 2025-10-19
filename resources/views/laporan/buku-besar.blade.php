@extends('layouts.admin')

@section('title', 'Laporan Buku Besar')

{{-- Menambahkan CSS untuk Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        {{-- CARD FILTER --}}
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Buku Besar</h3>
            </div>
            <form action="{{ route('laporan.bukuBesar') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="akun_id">Pilih Akun</label>
                                <select class="form-control select2bs4" id="akun_id" name="akun_id" required>
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" 
                                                data-saldo-normal="{{ $account->saldo_normal }}"
                                                {{-- 1. Tambahkan 'selected' jika akun ini yang dipilih --}}
                                                {{ $akunDipilih && $akunDipilih->id == $account->id ? 'selected' : '' }}>
                                            [{{ $account->kode_akun }}] {{ $account->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Tanggal Awal</label>
                                {{-- 2. Isi value dengan tanggal filter --}}
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
                                {{-- 2. Isi value dengan tanggal filter --}}
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}" required>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        {{-- 3. CARD HASIL LAPORAN --}}
        {{-- Tampilkan card ini HANYA JIKA user sudah filter ($akunDipilih ada isinya) --}}
        @if ($akunDipilih)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Buku Besar: <strong>[{{ $akunDipilih->kode_akun }}] {{ $akunDipilih->nama_akun }}</strong>
                </h3>
                <div class="card-tools">
                    Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr class="bg-light">
                            <th style="width: 10%;">Tanggal</th>
                            <th>Deskripsi</th>
                            <th style="width: 15%;">Debit</th>
                            <th style="width: 15%;">Kredit</th>
                            <th style="width: 15%;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Mulai saldo berjalan dari saldo awal
                            $saldo = $saldoAwal;
                        @endphp
                        {{-- Baris Saldo Awal --}}
                        <tr>
                            <td colspan="4" class="text-bold">Saldo Awal</td>
                            <td class="text-right text-bold">{{ number_format($saldo, 2, ',', '.') }}</td>
                        </tr>

                        {{-- Loop Mutasi Transaksi --}}
                        @forelse ($data as $detail)
                            @php
                                // Hitung Saldo Berjalan (Running Balance)
                                if ($akunDipilih->saldo_normal == 'Debit') {
                                    $saldo += $detail->debit;
                                    $saldo -= $detail->kredit;
                                } else { // Saldo normal 'Kredit'
                                    $saldo += $detail->kredit;
                                    $saldo -= $detail->debit;
                                }
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($detail->jurnal->tanggal_transaksi)->format('d-m-Y') }}</td>
                                <td>{{ $detail->jurnal->deskripsi }}</td>
                                <td class="text-right">{{ number_format($detail->debit, 2, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($detail->kredit, 2, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($saldo, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada transaksi pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        {{-- Baris Saldo Akhir --}}
                        <tr class="bg-light">
                            <td colspan="4" class="text-bold text-right">Saldo Akhir</td>
                            <td class="text-right text-bold">{{ number_format($saldo, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </div>
        @endif
        {{-- AKHIR CARD HASIL LAPORAN --}}
        
    </div>
</div>
@endsection

{{-- Menambahkan JS untuk Select2 --}}
@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    $(function() {
        // Inisialisasi Select2
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        // 4. Set nilai Select2 yang dipilih (jika ada)
        // Ini diperlukan agar setelah submit, dropdown tetap menampilkan akun yang dipilih
        let selectedAkunId = "{{ $akunDipilih ? $akunDipilih->id : '' }}";
        if (selectedAkunId) {
            $('#akun_id').val(selectedAkunId).trigger('change');
        }
    });
</script>
@endpush