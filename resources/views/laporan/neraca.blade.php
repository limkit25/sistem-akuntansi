@extends('layouts.admin')

@section('title', 'Laporan Neraca')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- CARD FILTER --}}
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Neraca</h3>
            </div>
            <form action="{{ route('laporan.neraca') }}" method="GET">
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
                    Laporan Neraca
                </h3>
                <div class="card-tools">
                    Periode: Per {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
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
                        {{-- ASET --}}
                        <tr class="bg-light-gray">
                            <th colspan="2">ASET</th>
                        </tr>
                        @forelse ($listAset as $data)
                            <tr>
                                <td class="pl-4">[{{ $data['kode_akun'] }}] {{ $data['nama_akun'] }}</td>
                                <td class="text-right">{{ number_format($data['total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="pl-4" colspan="2"><i>Tidak ada data Aset.</i></td>
                            </tr>
                        @endforelse
                        <tr class="bg-light">
                            <th class="text-right">TOTAL ASET</th>
                            <th class="text-right text-bold">{{ number_format($totalAset, 2, ',', '.') }}</th>
                        </tr>

                        {{-- LIABILITAS --}}
                        <tr class="bg-light-gray">
                            <th colspan="2">LIABILITAS (KEWAJIBAN)</th>
                        </tr>
                        @forelse ($listLiabilitas as $data)
                            <tr>
                                <td class="pl-4">[{{ $data['kode_akun'] }}] {{ $data['nama_akun'] }}</td>
                                <td class="text-right">{{ number_format($data['total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                             <tr>
                                <td class="pl-4" colspan="2"><i>Tidak ada data Liabilitas.</i></td>
                            </tr>
                        @endforelse
                        <tr class="bg-light">
                            <th class="text-right">Total Liabilitas</th>
                            <th class="text-right">{{ number_format($totalLiabilitas, 2, ',', '.') }}</th>
                        </tr>

                        {{-- EKUITAS --}}
                        <tr class="bg-light-gray">
                            <th colspan="2">EKUITAS (MODAL)</th>
                        </tr>
                        @forelse ($listEkuitas as $data)
                            <tr>
                                <td class="pl-4">[{{ $data['kode_akun'] }}] {{ $data['nama_akun'] }}</td>
                                <td class="text-right">{{ number_format($data['total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                             <tr>
                                <td class="pl-4" colspan="2"><i>Tidak ada data Ekuitas Awal.</i></td>
                            </tr>
                        @endforelse
                        {{-- Laba Rugi Berjalan --}}
                        <tr>
                            <td class="pl-4">Laba Rugi Tahun Berjalan</td>
                            <td class="text-right">{{ number_format($labaRugiBerjalan, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="bg-light">
                            <th class="text-right">Total Ekuitas</th>
                            <th class="text-right">{{ number_format($totalEkuitas, 2, ',', '.') }}</th>
                        </tr>
                    </tbody>
                    <tfoot>
                        {{-- TOTAL LIABILITAS + EKUITAS --}}
                        <tr class="bg-light">
                            <th class="text-right">TOTAL LIABILITAS DAN EKUITAS</th>
                            <th class="text-right text-bold">{{ number_format($totalLiabilitasDanEkuitas, 2, ',', '.') }}</th>
                        </tr>
                        
                        {{-- FINAL BALANCE CHECK --}}
                        @if(round($totalAset) == round($totalLiabilitasDanEkuitas))
                        <tr class="bg-success-light">
                            <th colspan="2" class="text-center">
                                <i class="fas fa-check-circle"></i> BALANCE
                            </h3>
                        </tr>
                        @else
                        <tr class="bg-danger-light">
                            <th class="text-right">SELISIH</th>
                            <th class="text-center text-bold">{{ number_format($totalAset - $totalLiabilitasDanEkuitas, 2, ',', '.') }}</th>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
            </div>
        
    </div>
</div>
@endsection