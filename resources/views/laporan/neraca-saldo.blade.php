@extends('layouts.admin')

@section('title', 'Laporan Neraca Saldo')

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
                <h3 class="card-title">Filter Neraca Saldo</h3>
            </div>
            <form action="{{ route('laporan.neracaSaldo') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        {{-- Filter Klinik (Hanya Superadmin) --}}
                        @role('Superadmin')
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="klinik_id">Pilih Klinik <span class="text-danger">*</span></label>
                                <select class="form-control select2bs4" id="klinik_id" name="klinik_id" required>
                                    <option value="">-- Pilih Klinik --</option>
                                    @foreach ($kliniks as $klinik)
                                        <option value="{{ $klinik->id }}"
                                            {{ $klinikIdFilter == $klinik->id ? 'selected' : '' }}>
                                            {{ $klinik->nama_klinik }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @else
                         {{-- Input hidden untuk Admin/Staf --}}
                         <input type="hidden" name="klinik_id" value="{{ Auth::user()->klinik_id }}">
                        @endrole

                        {{-- Filter Tanggal --}}
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Per Tanggal</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}" required>
                            </div>
                        </div>

                        {{-- Tombol Submit --}}
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
        {{-- Tampilkan hanya jika klinik sudah dipilih/ditentukan --}}
        @if ($klinikDipilih || (!$klinikDipilih && !Auth::user()->hasRole('Superadmin') && Auth::user()->klinik_id))
            @php $namaKlinikTampil = $klinikDipilih ? $klinikDipilih->nama_klinik : (Auth::user()->klinik->nama_klinik ?? 'Error'); @endphp
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Neraca Saldo - Klinik: <strong>{{ $namaKlinikTampil }}</strong>
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
                                    <td colspan="4" class="text-center">Tidak ada data transaksi untuk klinik ini sampai tanggal yang dipilih.</td>
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
                            @if(round($totalDebit, 2) == round($totalKredit, 2))
                            <tr class="bg-success-light">
                                <th colspan="4" class="text-center">
                                    <i class="fas fa-check-circle"></i> BALANCE
                                </th> {{-- Tutup tag th --}}
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
        @elseif(isset($error))
            <div class="alert alert-danger">{{ $error }}</div>
        @elseif(Auth::user()->hasRole('Superadmin') && !$klinikIdFilter)
            <div class="alert alert-info">Silakan pilih klinik untuk menampilkan Neraca Saldo.</div>
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
        if (selectedKlinikId) {
            $('#klinik_id').val(selectedKlinikId).trigger('change');
        }
    });
</script>
@endpush