@extends('layouts.admin')

@section('title', 'Tambah Akun Baru')

{{-- CSS for Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Formulir Akun Baru</h3>
            </div>
            <form action="{{ route('accounts.store') }}" method="POST">
                @csrf
                <div class="card-body">

                    {{-- Dropdown Klinik (Hanya Superadmin) --}}
                    @role('Superadmin')
                    <div class="form-group">
                        <label for="klinik_id">Klinik (Opsional)</label>
                        <select class="form-control select2bs4" id="klinik_id" name="klinik_id">
                            <option value="">-- Akun Global (Berlaku Semua Klinik) --</option>
                            @foreach ($kliniks as $klinik)
                                <option value="{{ $klinik->id }}" {{ old('klinik_id') == $klinik->id ? 'selected' : '' }}>
                                    {{ $klinik->nama_klinik }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Jika dikosongkan, akun ini berlaku global. Jika dipilih, hanya berlaku untuk klinik tersebut.</small>
                    </div>
                    @endrole
                    {{-- Input Hidden untuk Admin Klinik (otomatis set ID kliniknya) --}}
                    @unlessrole('Superadmin')
                         @if(Auth::user()->klinik_id)
                         <input type="hidden" name="klinik_id" value="{{ Auth::user()->klinik_id }}">
                         @else
                         <div class="alert alert-danger">Anda belum terhubung ke klinik.</div>
                         @endif
                    @endunlessrole


                    <div class="form-group">
                        <label for="kode_akun">Kode Akun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('kode_akun') is-invalid @enderror" id="kode_akun" name="kode_akun" placeholder="Contoh: 1101" value="{{ old('kode_akun') }}" required>
                         @error('kode_akun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                         <small class="form-text text-muted">Kode harus unik per klinik (atau unik jika global).</small>
                    </div>
                    <div class="form-group">
                        <label for="nama_akun">Nama Akun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_akun') is-invalid @enderror" id="nama_akun" name="nama_akun" placeholder="Contoh: Kas" value="{{ old('nama_akun') }}" required>
                         @error('nama_akun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="tipe_akun">Tipe Akun <span class="text-danger">*</span></label>
                        <select class="form-control @error('tipe_akun') is-invalid @enderror" id="tipe_akun" name="tipe_akun" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="Aset" {{ old('tipe_akun') == 'Aset' ? 'selected' : '' }}>Aset</option>
                            <option value="Liabilitas" {{ old('tipe_akun') == 'Liabilitas' ? 'selected' : '' }}>Liabilitas</option>
                            <option value="Ekuitas" {{ old('tipe_akun') == 'Ekuitas' ? 'selected' : '' }}>Ekuitas</option>
                            <option value="Pendapatan" {{ old('tipe_akun') == 'Pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                            <option value="Biaya" {{ old('tipe_akun') == 'Biaya' ? 'selected' : '' }}>Biaya</option>
                        </select>
                         @error('tipe_akun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="saldo_normal">Saldo Normal <span class="text-danger">*</span></label>
                        <select class="form-control @error('saldo_normal') is-invalid @enderror" id="saldo_normal" name="saldo_normal" required>
                            <option value="">-- Pilih Saldo Normal --</option>
                            <option value="Debit" {{ old('saldo_normal') == 'Debit' ? 'selected' : '' }}>Debit</option>
                            <option value="Kredit" {{ old('saldo_normal') == 'Kredit' ? 'selected' : '' }}>Kredit</option>
                        </select>
                         @error('saldo_normal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-footer">
                    {{-- Nonaktifkan tombol jika Admin Klinik tidak punya klinik_id --}}
                    <button type="submit" class="btn btn-primary" {{ !Auth::user()->hasRole('Superadmin') && !Auth::user()->klinik_id ? 'disabled' : '' }}>Simpan</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
        </div>
</div>
@endsection

@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    $(function() {
        // Inisialisasi Select2
        $('.select2bs4').select2({
            theme: 'bootstrap4',
            placeholder: $(this).data('placeholder'),
             allowClear: true // Allow clearing selection for Klinik (Superadmin)
        });
    });
</script>
@endpush