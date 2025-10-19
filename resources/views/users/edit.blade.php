@extends('layouts.admin')

@section('title', 'Edit User: ' . $user->name)

{{-- CSS untuk Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Formulir Edit User</h3>
            </div>
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nama</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" disabled>
                        <small class="form-text text-muted">Nama tidak bisa diubah dari sini.</small>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="role">Role / Peran</label>
                        <select class="form-control" id="role" name="role" required
                                {{-- Jangan biarkan Superadmin (ID 1) mengubah role-nya sendiri --}}
                                {{ $user->id == 1 ? 'disabled' : '' }}>

                            <option value="">-- Pilih Role --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}"
                                    {{-- Pilih role yang dimiliki user saat ini --}}
                                    {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @if($user->id == 1)
                            <small class="form-text text-danger">Role Superadmin utama (ID 1) tidak dapat diubah.</small>
                            {{-- 1. HAPUS INPUT HIDDEN DI SINI --}}
                        @endif
                    </div>
                    {{-- Dropdown Klinik --}}
                    <div class="form-group">
                        <label for="klinik_id">Klinik (Wajib untuk Staf & Admin)</label>
                        {{-- Tambahkan kelas select2bs4 --}}
                        <select class="form-control select2bs4" id="klinik_id" name="klinik_id"
                                {{-- Nonaktifkan jika user adalah Superadmin (ID 1) --}}
                                {{ $user->id == 1 ? 'disabled' : '' }}>

                            <option value="">-- Tidak Terhubung ke Klinik (Hanya Superadmin) --</option>
                            @foreach ($kliniks as $klinik)
                                <option value="{{ $klinik->id }}"
                                    {{-- Pilih klinik yang dimiliki user saat ini --}}
                                    {{ old('klinik_id', $user->klinik_id) == $klinik->id ? 'selected' : '' }}>
                                    {{ $klinik->nama_klinik }}
                                </option>
                            @endforeach
                        </select>
                        @if($user->id == 1)
                            <small class="form-text text-muted">Superadmin tidak terikat ke klinik spesifik.</small>
                        @else
                            <small class="form-text text-muted">User dengan role 'Admin' atau 'Staf' harus terhubung ke satu klinik.</small>
                        @endif
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Update User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
        </div>
</div>
@endsection

{{-- 2. TAMBAHKAN BLOK @push('js') INI --}}
@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    $(function() {
        // Inisialisasi Select2 untuk dropdown Klinik
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });
    });
</script>
@endpush