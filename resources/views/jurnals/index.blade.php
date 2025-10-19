@extends('layouts.admin')

@section('title', 'Daftar Jurnal Umum')

{{-- CSS for Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        {{-- CARD FILTER --}}
        {{-- Tampilkan HANYA untuk Superadmin --}}
        @role('Superadmin')
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Jurnal</h3>
            </div>
            <form action="{{ route('jurnals.index') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="klinik_id">Pilih Klinik</label>
                                <select class="form-control select2bs4" id="klinik_id" name="klinik_id" required>
                                    {{-- Opsi default tergantung apakah ada klinik dipilih --}}
                                    @if($selectedKlinikId)
                                        <option value="">-- Tampilkan Semua Periode Klinik Ini --</option>
                                    @else
                                        <option value="">-- Pilih Klinik untuk Menampilkan Periode --</option>
                                    @endif

                                    @foreach ($kliniks as $klinik)
                                        <option value="{{ $klinik->id }}"
                                            {{ $selectedKlinikId == $klinik->id ? 'selected' : '' }}>
                                            {{ $klinik->nama_klinik }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Tampilkan Periode
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @else
        {{-- Info untuk Admin/Staf --}}
        <div class="alert alert-info">
            Menampilkan data untuk klinik: <strong>{{ Auth::user()->klinik->nama_klinik ?? 'Klinik tidak terhubung' }}</strong>
        </div>
        @endrole
        {{-- CARD HASIL JURNAL --}}
        <div class="card">
            <div class="card-header">
                {{-- Hanya izinkan buat jurnal jika user punya akses ke klinik (Admin/Staf harus punya klinik_id) --}}
                @if(Auth::user()->hasRole('Superadmin') || Auth::user()->klinik_id)
                <a href="{{ route('jurnals.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Jurnal Baru
                </a>
                @endif
            </div>
            <div class="card-body">
                {{-- Cek error (misal Staf tanpa klinik) --}}
                @if(isset($error))
                    <div class="alert alert-danger">{{ $error }}</div>
                @elseif(!$selectedKlinikId && Auth::user()->hasRole('Superadmin') && request()->filled('klinik_id') && request('klinik_id') != '')
                    {{-- Superadmin memilih klinik tapi klinik itu tidak ada/tidak punya jurnal --}}
                     <div class="alert alert-info">
                         Klinik yang dipilih tidak ditemukan atau tidak memiliki data jurnal. Silakan pilih klinik lain.
                    </div>
                @elseif(!$selectedKlinikId && Auth::user()->hasRole('Superadmin'))
                     {{-- Superadmin belum memilih klinik --}}
                    <div class="alert alert-info">
                        Silakan pilih klinik terlebih dahulu untuk menampilkan periode jurnal.
                    </div>
                @elseif($periods->isEmpty() && $selectedKlinikId)
                    {{-- Admin/Staf atau Superadmin sudah pilih klinik, tapi tidak ada jurnal --}}
                    <div class="alert alert-info">
                        Belum ada data jurnal untuk klinik ini pada periode manapun.
                    </div>
                @elseif($periods->isEmpty() && !$selectedKlinikId)
                     {{-- Kondisi aneh, seharusnya tidak terjadi jika logika controller benar --}}
                     <div class="alert alert-warning">
                        Tidak ada periode jurnal yang bisa ditampilkan.
                    </div>
                @else
                    {{-- Tampilkan tabel periode --}}
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Periode Jurnal</th>
                                <th style="width: 20%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periods as $period)
                                <tr>
                                    <td>
                                        <h4>{{ $period->period_name }}</h4>
                                    </td>
                                    <td>
                                        {{-- Link "Lihat Detail" termasuk clinic ID --}}
                                        <a href="{{ route('jurnals.showMonthly', ['klinik' => $selectedKlinikId, 'period' => $period->period_value]) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            </div>
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
    });
</script>
@endpush