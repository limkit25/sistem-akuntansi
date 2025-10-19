@extends('layouts.admin')

@section('title', 'Laporan Buku Besar')

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
                <h3 class="card-title">Filter Buku Besar</h3>
            </div>

            <form action="{{ route('laporan.bukuBesar') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        {{-- Filter Klinik (Hanya Superadmin) --}}
                        @role('Superadmin')
                        <div class="col-md-3"> {{-- Adjust width --}}
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

                        {{-- Filter Akun --}}
                        <div class="col-md-3"> {{-- Adjust width --}}
                            <div class="form-group">
                                <label for="akun_id">Pilih Akun <span class="text-danger">*</span></label>
                                <select class="form-control select2bs4" id="akun_id" name="akun_id" required>
                                    <option value="">-- Pilih Akun --</option>
                                    {{-- Opsi Akun akan bergantung pada pilihan klinik (jika Superadmin) atau klinik user --}}
                                    {{-- Sebaiknya load akun via AJAX setelah klinik dipilih, atau kirim semua akun relevan dari controller --}}
                                    {{-- Untuk sekarang, kita asumsikan controller mengirim semua akun relevan ($accounts) --}}
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}"
                                                data-saldo-normal="{{ $account->saldo_normal }}"
                                                {{ $akunIdFilter == $account->id ? 'selected' : '' }}>
                                             {{ $account->klinik_id === null ? '[G]' : '' }} [{{ $account->kode_akun }}] {{ $account->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Filter Tanggal --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="start_date">Tanggal Awal</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}" required>
                            </div>
                        </div>

                        {{-- Tombol Submit --}}
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
        {{-- CARD HASIL LAPORAN --}}
        {{-- Tampilkan hanya jika SEMUA filter (klinik & akun) sudah dipilih/ditentukan --}}
        @if ($klinikDipilih && $akunDipilih)
            @php $namaKlinikTampil = $klinikDipilih->nama_klinik; @endphp
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Buku Besar: <strong>[{{ $akunDipilih->kode_akun }}] {{ $akunDipilih->nama_akun }}</strong> <br>
                        <small>Klinik: {{ $namaKlinikTampil }}</small>
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
                            @php $saldo = $saldoAwal; @endphp
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
                                        $saldo += $detail->debit - $detail->kredit;
                                    } else { // Saldo normal 'Kredit'
                                        $saldo += $detail->kredit - $detail->debit;
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
                                    <td colspan="5" class="text-center">Tidak ada transaksi pada periode ini untuk akun dan klinik yang dipilih.</td>
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
         @elseif(isset($error)) {{-- Error jika Admin/Staf tidak punya klinik --}}
            <div class="alert alert-danger">{{ $error }}</div>
        @elseif(Auth::user()->hasRole('Superadmin') && !$klinikIdFilter && $akunIdFilter)
             <div class="alert alert-warning">Silakan pilih Klinik terlebih dahulu.</div>
        @elseif($klinikIdFilter && !$akunIdFilter)
             <div class="alert alert-warning">Silakan pilih Akun untuk menampilkan Buku Besar.</div>
        @elseif(Auth::user()->hasRole('Superadmin') && !$klinikIdFilter && !$akunIdFilter)
            <div class="alert alert-info">Silakan pilih Klinik dan Akun untuk menampilkan laporan Buku Besar.</div>
        @endif
        {{-- AKHIR CARD HASIL LAPORAN --}}

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
            theme: 'bootstrap4',
            placeholder: $(this).data('placeholder') || '-- Pilih --', // Generic placeholder
            allowClear: true // Allow clearing selection
        });

        // Set nilai Select2 yang dipilih (jika ada)
        let selectedKlinikId = "{{ $klinikIdFilter ?? '' }}";
        if (selectedKlinikId) {
            $('#klinik_id').val(selectedKlinikId).trigger('change');
        }
        let selectedAkunId = "{{ $akunIdFilter ?? '' }}";
        if (selectedAkunId) {
            $('#akun_id').val(selectedAkunId).trigger('change');
        }

        // TODO (Opsional): Implementasi AJAX untuk memuat Akun berdasarkan Klinik
        // $('#klinik_id').on('change', function() {
        //     let klinikId = $(this).val();
        //     let akunSelect = $('#akun_id');
        //     akunSelect.prop('disabled', true).empty().append(new Option('Memuat Akun...', '', true, true)).trigger('change');
        //     if(klinikId) {
        //         // Panggil AJAX ke route baru (misal /get-accounts-for-report/{klinikId})
        //         // Success: Isi ulang akunSelect, enable lagi
        //     } else {
        //          akunSelect.prop('disabled', false).empty().append(new Option('-- Pilih Akun --', '', true, true)).trigger('change'); // Reset jika klinik kosong
        //     }
        // });
    });
</script>
@endpush