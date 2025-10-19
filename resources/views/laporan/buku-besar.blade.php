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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="klinik_id">Pilih Klinik / Konteks <span class="text-danger">*</span></label>
                                <select class="form-control select2bs4 @error('klinik_id') is-invalid @enderror" id="klinik_id" name="klinik_id" required>
                                    <option value="">-- Pilih Konteks --</option>
                                    {{-- OPSI Global --}}
                                    <option value="global" {{ $klinikIdFilter === 'global' ? 'selected' : '' }}>-- Hanya Akun Global --</option>
                                    {{-- OPSI Klinik Spesifik --}}
                                    @foreach ($kliniks as $klinik)
                                        <option value="{{ $klinik->id }}"
                                            {{ $klinikIdFilter == $klinik->id ? 'selected' : '' }}>
                                            {{ $klinik->nama_klinik }} (Spesifik)
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

                        {{-- Filter Akun --}}
                        <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-3' : 'col-md-4' }}">
                            <div class="form-group">
                                <label for="akun_id">Pilih Akun <span class="text-danger">*</span></label>
                                <select class="form-control select2bs4 @error('akun_id') is-invalid @enderror" id="akun_id" name="akun_id" required>
                                    <option value="">-- Pilih Akun --</option>
                                    {{-- Gunakan $accountsForDropdown --}}
                                    @foreach ($accountsForDropdown as $account)
                                        <option value="{{ $account->id }}"
                                                data-saldo-normal="{{ $account->saldo_normal }}"
                                                {{ $akunIdFilter == $account->id ? 'selected' : '' }}>
                                             {{ $account->klinik_id === null ? '[G]' : '' }} [{{ $account->kode_akun }}] {{ $account->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('akun_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Filter Tanggal --}}
                         <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-2' : 'col-md-3' }}">
                            <div class="form-group">
                                <label for="start_date">Tanggal Awal</label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ $startDate }}" required>
                                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                         <div class="{{ Auth::user()->hasRole('Superadmin') ? 'col-md-2' : 'col-md-3' }}">
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ $endDate }}" required>
                                @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
        @php
            $showReport = false;
            $namaKlinikTampil = '-'; // Default

            // Tentukan konteks laporan (Global atau Nama Klinik)
            if ($klinikIdFilter === 'global' && $akunDipilih && $akunDipilih->klinik_id === null) {
                 $namaKlinikTampil = 'Global';
                 $showReport = true;
            } elseif (is_numeric($klinikIdFilter) && $klinikDipilih && $akunDipilih && ($akunDipilih->klinik_id === null || $akunDipilih->klinik_id == $klinikIdFilter)) {
                 $namaKlinikTampil = $klinikDipilih->nama_klinik;
                 $showReport = true;
            } elseif (!Auth::user()->hasRole('Superadmin') && Auth::user()->klinik_id && $akunDipilih && ($akunDipilih->klinik_id === null || $akunDipilih->klinik_id == Auth::user()->klinik_id)) {
                 $namaKlinikTampil = Auth::user()->klinik->nama_klinik ?? 'Klinik Error';
                 $showReport = true;
            }
        @endphp

        {{-- Tampilkan tabel laporan HANYA jika showReport true --}}
        @if ($showReport)
            <div class="card">
                <div class="card-header">
                     <h3 class="card-title">
                        Buku Besar: <strong>[{{ $akunDipilih->kode_akun }}] {{ $akunDipilih->nama_akun }}</strong> <br>
                        <small>Konteks: {{ $namaKlinikTampil }}</small>
                    </h3>
                    <div class="card-tools">
                        Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </div>
                </div>
                <div class="card-body p-0">
                     {{-- Tampilkan warning jika akun tidak valid (dari Controller) --}}
                    @if(session('warning'))
                        <div class="alert alert-warning m-2">{{ session('warning') }}</div>
                    @endif
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
                            <tr>
                                <td colspan="4" class="text-bold">Saldo Awal</td>
                                <td class="text-right text-bold">{{ number_format($saldo, 2, ',', '.') }}</td>
                            </tr>
                            @forelse ($data as $detail)
                                @php
                                    if ($akunDipilih->saldo_normal == 'Debit') {
                                        $saldo += $detail->debit - $detail->kredit;
                                    } else {
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
                                    <td colspan="5" class="text-center">Tidak ada transaksi pada periode ini untuk akun dan konteks klinik yang dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="4" class="text-bold text-right">Saldo Akhir</td>
                                <td class="text-right text-bold">{{ number_format($saldo, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
         {{-- Kondisi lain untuk menampilkan pesan info/error --}}
         @elseif(isset($error))
            <div class="alert alert-danger">{{ $error }}</div>
         @elseif(Auth::user()->hasRole('Superadmin') && !$klinikIdFilter)
            <div class="alert alert-info">Silakan pilih Konteks Klinik/Global dan Akun untuk menampilkan laporan.</div>
         @elseif($klinikIdFilter && !$akunIdFilter && !isset($error))
             <div class="alert alert-warning">Silakan pilih Akun untuk menampilkan Buku Besar.</div>
         @elseif(session('warning')) {{-- Tampilkan warning jika akun tidak valid --}}
             <div class="alert alert-warning">{{ session('warning') }} Silakan pilih Akun yang sesuai.</div>
         @elseif(!Auth::user()->hasRole('Superadmin') && Auth::user()->klinik_id && !$akunIdFilter)
             <div class="alert alert-info">Silakan pilih Akun untuk menampilkan Buku Besar klinik Anda.</div>
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
            placeholder: $(this).data('placeholder') || '-- Pilih --',
            allowClear: true
        });

        // Set nilai Select2 yang dipilih (jika ada)
        let selectedKlinikId = "{{ $klinikIdFilter ?? '' }}"; // Bisa 'global' atau ID
        if (selectedKlinikId) {
            $('#klinik_id').val(selectedKlinikId).trigger('change');
        }
        let selectedAkunId = "{{ $akunIdFilter ?? '' }}";
        if (selectedAkunId) {
            $('#akun_id').val(selectedAkunId).trigger('change');
        }

        // TODO (SANGAT DIREKOMENDASIKAN): Implementasi AJAX untuk memuat Akun berdasarkan Klinik/Global
        $('#klinik_id').on('change', function() {
            let klinikContext = $(this).val(); // Bisa 'global' atau ID
            let akunSelect = $('#akun_id');

            // Kosongkan dan nonaktifkan dropdown akun saat memuat
            akunSelect.prop('disabled', true).empty().append(new Option('Memuat Akun...', '', true, true)).trigger('change');

            if(klinikContext) {
                 // Buat route AJAX baru yg bisa handle 'global' atau ID
                 // Nama route ini perlu dibuat di web.php
                let url = "{{ url('/get-accounts-for-report') }}/" + klinikContext;
                 $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(accountsData) {
                        // Hapus opsi loading dan tambahkan opsi default
                        akunSelect.empty().append(new Option('-- Pilih Akun --', '', true, true));
                        // Isi dengan data baru
                        accountsData.forEach(function(account) {
                             let prefix = account.klinik_id === null ? '[G] ' : '';
                            akunSelect.append(new Option(`${prefix}[${account.kode_akun}] ${account.nama_akun}`, account.id));
                        });
                        // Aktifkan kembali dan trigger change
                        akunSelect.prop('disabled', false).trigger('change');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        toastr.error('Gagal memuat daftar akun.');
                        // Tampilkan pesan error di dropdown
                         akunSelect.prop('disabled', false).empty().append(new Option('-- Gagal Memuat Akun --', '', true, true)).trigger('change');
                    }
                 });
            } else {
                 // Jika tidak ada konteks dipilih, kosongkan & enable dropdown akun
                 akunSelect.prop('disabled', false).empty().append(new Option('-- Pilih Konteks Klinik/Global Dulu --', '', true, true)).trigger('change');
            }
        });
        // Jika Superadmin & ada filter klinik awal, trigger change untuk load akun awal
        @role('Superadmin')
        if(selectedKlinikId){
            // Kita beri sedikit delay agar Select2 Klinik sempat terinisialisasi
            setTimeout(function() {
                $('#klinik_id').trigger('change');
                 // Setelah akun dimuat, set nilai akun yang dipilih jika ada
                setTimeout(function(){
                     if (selectedAkunId) {
                        $('#akun_id').val(selectedAkunId).trigger('change');
                    }
                }, 500); // Delay tambahan
            }, 100);
        } else if (selectedAkunId) { // Jika hanya akun yang dipilih (tidak seharusnya terjadi tanpa klinik)
             $('#akun_id').val(selectedAkunId).trigger('change');
        }
        @else
            // Jika Admin/Staf dan ada akun filter awal
            if (selectedAkunId) {
                 $('#akun_id').val(selectedAkunId).trigger('change');
            }
        @endrole

    });
</script>
{{-- Jangan lupa buat route AJAX '/get-accounts-for-report/{context}' di web.php --}}
@endpush