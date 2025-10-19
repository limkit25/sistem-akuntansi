@extends('layouts.admin')

@section('title', 'Laporan Neraca Saldo')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- CARD FILTER --}}
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Neraca Saldo</h3>
            </div>
            <form action="{{ route('laporan.neracaSaldo') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">Per Tanggal</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}" required>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
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
        {{-- CARD HASIL LAPORAN --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Neraca Saldo
                </h3>
                <div class="card-tools">
                    Periode: Per {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr class="bg-light">
                            <th style="width: 15%;">Kode Akun</th>
                            <th>Nama Akun</th>
                            <th style="width: 20%;">Debit</th>
                            <th style="width: 20%;">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporanData as $data)
                            <tr>
                                <td>{{ $data['kode_akun'] }}</td>
                                <td>{{ $data['nama_akun'] }}</td>
                                <td class="text-right">{{ number_format($data['debit'], 2, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($data['kredit'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="2" class="text-right">TOTAL</th>
                            <th class="text-right text-bold">{{ number_format($totalDebit, 2, ',', '.') }}</th>
                            <th class="text-right text-bold">{{ number_format($totalKredit, 2, ',', '.') }}</th>
                        </tr>
                        {{-- Cek Balance --}}
                        @if($totalDebit == $totalKredit)
                        <tr class="bg-success-light">
                            <th colspan="4" class="text-center">
                                <i class="fas fa-check-circle"></i> BALANCE
                            </h3>
                        </tr>
                        @else
                        <tr class="bg-danger-light">
                            <th colspan="2" class="text-right">SELISIH</th>
                            <th colspan="2" class="text-center text-bold">{{ number_format($totalDebit - $totalKredit, 2, ',', '.') }}</th>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
            </div>
        
    </div>
</div>
@endsection