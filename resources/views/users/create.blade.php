@extends('layouts.admin')

@section('title', 'Tambah User Baru')

{{-- CSS untuk Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Formulir User Baru</h3>
            </div>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                         @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                         @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                     <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role / Peran <span class="text-danger">*</span></label>
                        <select class="form-control @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">-- Pilih Role --</option>
                            @foreach ($roles as $role)
                                {{-- Jangan izinkan Superadmin membuat Superadmin baru --}}
                                @if($role->name != 'Superadmin')
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                         @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="klinik_id">Klinik (Wajib untuk Staf & Admin)</label>
                        <select class="form-control select2bs4 @error('klinik_id') is-invalid @enderror" id="klinik_id" name="klinik_id">
                            <option value="">-- Pilih Klinik --</option>
                            @foreach ($kliniks as $klinik)
                                <option value="{{ $klinik->id }}" {{ old('klinik_id') == $klinik->id ? 'selected' : '' }}>
                                    {{ $klinik->nama_klinik }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Kosongkan jika role adalah Superadmin.</small>
                         @error('klinik_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Simpan User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
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
            placeholder: '-- Pilih Klinik --',
            allowClear: true // Allow clearing the selection
        });

        // Logika untuk mewajibkan Klinik ID berdasarkan Role
        $('#role').on('change', function() {
            const selectedRole = $(this).val();
            const klinikSelect = $('#klinik_id');
            const klinikLabel = $('label[for="klinik_id"]');

            if (selectedRole === 'Admin' || selectedRole === 'Staf') {
                klinikLabel.html('Klinik <span class="text-danger">*</span>');
                // Anda bisa menambahkan atribut 'required' jika mau, tapi validasi di controller lebih aman
                // klinikSelect.prop('required', true);
            } else {
                klinikLabel.html('Klinik (Opsional)');
                // klinikSelect.prop('required', false);
                 klinikSelect.val(null).trigger('change'); // Kosongkan jika Superadmin
            }
        }).trigger('change'); // Trigger saat halaman load
    });
</script>
@endpush