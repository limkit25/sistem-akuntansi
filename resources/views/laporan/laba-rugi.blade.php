@extends('layouts.admin')

@section('title', 'Laporan Laba Rugi')

{{-- CSS for Select2 --}}
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
                <h3 class="card-title">Filter Laporan</h3>
            </div>
            <form action="{{ route('laporan.labaRugi') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        {{-- Filter Klinik (Hanya Superadmin) --}}
                        @role('Superadmin')
                        <div class="col-md-3"> {{-- Sesuaikan lebar kolom --}}
                            <div class="form-group">
                                <label for="klinik_id">Pilih Klinik <span class="text-danger">*</span></label>
                                <select class="form-control select2bs4 @error('klinik_id') is-invalid @enderror" id="klinik_id" name="klinik_id" required>
                                    <option value="">-- Pilih Klinik --</option>
                                    @foreach ($kliniks as $klinik)
                                        <option value="{{ $klinik->id }}"
                                            {{ $klinikIdFilter == $klinik->id ? 'selected' : '' }}>
                                            {{ $klinik->nama_klinik }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('klinik_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        @else
                         {{-- Input hidden untuk Admin/Staf --}}
                         <input type="hidden" name="klinik_id" value="{{ Auth::user()->klinik_id }}">
                        @endrole

                        {{-- Filter Tanggal --}}
                         <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}"> {{-- Sesuaikan lebar --}}
                            <div class="form-group">
                                <label for="start_date">Tanggal Awal</label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ $startDate }}" required>
                                 @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                         <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}"> {{-- Sesuaikan lebar --}}
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ $endDate }}" required>
                                 @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Tombol Submit --}}
                         <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-2' : 'col-md-3' }} d-flex align-items-end"> {{-- Sesuaikan lebar --}}
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
         @php
            $namaKlinikTampil = '-';
            if ($klinikDipilih) {
                $namaKlinikTampil = $klinikDipilih->nama_klinik;
            } elseif (!Auth::user()->hasRole('Superadmin') && Auth::user()->klinik_id) {
                $namaKlinikTampil = Auth::user()->klinik->nama_klinik ?? 'Klinik Error';
            }
        @endphp

        {{-- Tampilkan hanya jika klinik sudah dipilih/ditentukan & bukan error --}}
        @if (is_numeric($klinikIdFilter) && !isset($error))
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Laporan Laba Rugi - Klinik: <strong>{{ $namaKlinikTampil }}</strong>
                    </h3>
                    <div class="card-tools">
                        Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </div>
                    <div class="btn-group">
                            @php
                                $exportParams = [
                                    'klinik_id' => $klinikIdFilter,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                ];
                            @endphp
                            <a href="{{ route('laporan.labaRugi.export', array_merge($exportParams, ['type' => 'excel'])) }}" class="btn btn-sm btn-success" target="_blank">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                            <a href="{{ route('laporan.labaRugi.export', array_merge($exportParams, ['type' => 'pdf'])) }}" class="btn btn-sm btn-danger" target="_blank">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
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
                                    {{-- Tambah prefix [G] jika akun global --}}
                                    <td class="pl-4">
                                        {{ App\Models\ChartOfAccount::where('kode_akun', $data['kode_akun'])->whereNull('klinik_id')->exists() ? '[G] ' : '' }}
                                        [{{ $data['kode_akun'] }}] {{ $data['nama_akun'] }}
                                    </td>
                                    <td class="text-right">{{ number_format($data['total'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="pl-4" colspan="2"><i>Tidak ada data pendapatan pada periode ini.</i></td>
                                </tr>
                            @endforelse
                            <tr class="bg-light">
                                <th class="text-right">Total Pendapatan</th>
                                <th class="text-right text-bold">{{ number_format($totalPendapatan, 2, ',', '.') }}</th>
                            </tr>

                            {{-- BIAYA --}}
                            <tr class="bg-light-gray">
                                <th colspan="2">Biaya</th>
                            </tr>
                            @forelse ($listBiaya as $data)
                                <tr>
                                     {{-- Tambah prefix [G] jika akun global --}}
                                    <td class="pl-4">
                                         {{ App\Models\ChartOfAccount::where('kode_akun', $data['kode_akun'])->whereNull('klinik_id')->exists() ? '[G] ' : '' }}
                                        [{{ $data['kode_akun'] }}] {{ $data['nama_akun'] }}
                                    </td>
                                    <td class="text-right">{{ number_format($data['total'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                 <tr>
                                    <td class="pl-4" colspan="2"><i>Tidak ada data biaya pada periode ini.</i></td>
                                </tr>
                            @endforelse
                            <tr class="bg-light">
                                <th class="text-right">Total Biaya</th>
                                <th class="text-right text-bold">{{ number_format($totalBiaya, 2, ',', '.') }}</th>
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
         {{-- Kondisi pesan error / info --}}
         @elseif(isset($error))
            <div class="alert alert-danger">{{ $error }}</div>
         @elseif(Auth::user()->hasRole('Superadmin') && !$klinikIdFilter)
            <div class="alert alert-info">Silakan pilih klinik untuk menampilkan Laporan Laba Rugi.</div>
         @endif

    </div>
</div>
@endsection

{{-- JS for Select2 --}}
@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    $(function() {
        // Inisialisasi Select2
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        // Set nilai Select2 Klinik yang dipilih (jika Superadmin)
        let selectedKlinikId = "{{ $klinikIdFilter ?? '' }}";
         if (selectedKlinikId && $('#klinik_id').length) {
            $('#klinik_id').val(selectedKlinikId).trigger('change');
        }
    });
</script>
@endpush