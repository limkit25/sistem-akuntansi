@extends('layouts.admin')

@section('title', 'Edit Akun: ' . $account->nama_akun)

{{-- CSS for Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Formulir Edit Akun</h3>
            </div>
            <form action="{{ route('accounts.update', $account->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">

                    {{-- Dropdown Klinik (Hanya bisa diubah Superadmin) --}}
                    @role('Superadmin')
                    <div class="form-group">
                        <label for="klinik_id">Klinik</label>
                        <select class="form-control select2bs4" id="klinik_id" name="klinik_id">
                            <option value="">-- Akun Global (Berlaku Semua Klinik) --</option>
                            @foreach ($kliniks as $klinik)
                                <option value="{{ $klinik->id }}" {{ old('klinik_id', $account->klinik_id) == $klinik->id ? 'selected' : '' }}>
                                    {{ $klinik->nama_klinik }}
                                </option>
                            @endforeach
                        </select>
                         <small class="form-text text-muted">Mengubah klinik akan memengaruhi filter laporan.</small>
                    </div>
                    @else
                     {{-- Tampilkan info klinik (tidak bisa diubah Admin Klinik) --}}
                     <div class="form-group">
                        <label>Klinik</label>
                        <input type="text" class="form-control"
                               value="{{ $account->klinik->nama_klinik ?? 'Global (Tidak bisa diubah)' }}" disabled>
                        {{-- Kirim ID lama via hidden input jika diperlukan oleh controller (meskipun controller sdh handle) --}}
                        <input type="hidden" name="klinik_id" value="{{ $account->klinik_id }}">
                     </div>
                    @endrole

                    <div class="form-group">
                        <label for="kode_akun">Kode Akun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('kode_akun') is-invalid @enderror" id="kode_akun" name="kode_akun" value="{{ old('kode_akun', $account->kode_akun) }}" required>
                        @error('kode_akun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                         <small class="form-text text-muted">Kode harus unik per klinik (atau unik jika global).</small>
                    </div>
                    <div class="form-group">
                        <label for="nama_akun">Nama Akun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_akun') is-invalid @enderror" id="nama_akun" name="nama_akun" value="{{ old('nama_akun', $account->nama_akun) }}" required>
                         @error('nama_akun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="tipe_akun">Tipe Akun <span class="text-danger">*</span></label>
                        <select class="form-control @error('tipe_akun') is-invalid @enderror" id="tipe_akun" name="tipe_akun" required>
                             <option value="">-- Pilih Tipe --</option>
                            <option value="Aset" {{ old('tipe_akun', $account->tipe_akun) == 'Aset' ? 'selected' : '' }}>Aset</option>
                            <option value="Liabilitas" {{ old('tipe_akun', $account->tipe_akun) == 'Liabilitas' ? 'selected' : '' }}>Liabilitas</option>
                            <option value="Ekuitas" {{ old('tipe_akun', $account->tipe_akun) == 'Ekuitas' ? 'selected' : '' }}>Ekuitas</option>
                            <option value="Pendapatan" {{ old('tipe_akun', $account->tipe_akun) == 'Pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                            <option value="Biaya" {{ old('tipe_akun', $account->tipe_akun) == 'Biaya' ? 'selected' : '' }}>Biaya</option>
                        </select>
                         @error('tipe_akun') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="saldo_normal">Saldo Normal <span class="text-danger">*</span></label>
                        <select class="form-control @error('saldo_normal') is-invalid @enderror" id="saldo_normal" name="saldo_normal" required>
                            <option value="">-- Pilih Saldo Normal --</option>
                            <option value="Debit" {{ old('saldo_normal', $account->saldo_normal) == 'Debit' ? 'selected' : '' }}>Debit</option>
                            <option value="Kredit" {{ old('saldo_normal', $account->saldo_normal) == 'Kredit' ? 'selected' : '' }}>Kredit</option>
                        </select>
                         @error('saldo_normal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-footer">
                    {{-- Nonaktifkan tombol jika Admin Klinik mencoba edit akun global --}}
                     <button type="submit" class="btn btn-warning" {{ !Auth::user()->hasRole('Superadmin') && $account->klinik_id === null ? 'disabled' : '' }}>Update</button>
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
             allowClear: true
        });
    });
</script>
@endpush