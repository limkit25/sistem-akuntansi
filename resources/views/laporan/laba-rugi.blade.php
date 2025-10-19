@extends('layouts.admin')

@section('title', 'Laporan Laba Rugi')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- CARD FILTER --}}
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Laporan</h3>
            </div>
            <form action="{{ route('laporan.labaRugi') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">Tanggal Awal</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
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
                    Laporan Laba Rugi
                </h3>
                <div class="card-tools">
                    Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered">
                    <thead>
                        <tr class="bg-light">
                            <th style="width: 70%;">Deskripsi Akun</th>
                            <th style="width: 30%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- PENDAPATAN --}}
                        <tr class="bg-light-gray">
                            <th colspan="2">Pendapatan</th>
                        </tr>
                        @forelse ($listPendapatan as $data)
                            <tr>
                                <td class="pl-4">[{{ $data['kode_akun'] }}] {{ $data['nama_akun'] }}</td>
                                <td class="text-right">{{ number_format($data['total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="pl-4" colspan="2"><i>Tidak ada data pendapatan.</i></td>
                            </tr>
                        @endforelse
                        <tr class="bg-light">
                            <th class="text-right">Total Pendapatan</th>
                            <th class="text-right">{{ number_format($totalPendapatan, 2, ',', '.') }}</th>
                        </tr>

                        {{-- BIAYA --}}
                        <tr class="bg-light-gray">
                            <th colspan="2">Biaya</th>
                        </tr>
                        @forelse ($listBiaya as $data)
                            <tr>
                                <td class="pl-4">[{{ $data['kode_akun'] }}] {{ $data['nama_akun'] }}</td>
                                <td class="text-right">{{ number_format($data['total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                             <tr>
                                <td class="pl-4" colspan="2"><i>Tidak ada data biaya.</i></td>
                            </tr>
                        @endforelse
                        <tr class="bg-light">
                            <th class="text-right">Total Biaya</th>
                            <th class="text-right">{{ number_format($totalBiaya, 2, ',', '.') }}</th>
                        </tr>
                        
                    </tbody>
                    <tfoot>
                        {{-- LABA/RUGI --}}
                        @if($labaRugi >= 0)
                            <tr class="bg-success-light">
                                <th class="text-right">LABA BERSIH</th>
                                <th class="text-right text-bold">{{ number_format($labaRugi, 2, ',', '.') }}</th>
                            </tr>
                        @else
                            <tr class="bg-danger-light">
                                <th class="text-right">RUGI BERSIH</th>
                                <th class="text-right text-bold">{{ number_format($labaRugi, 2, ',', '.') }}</th>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
            </div>
        
    </div>
</div>
@endsection