@extends('layouts.admin')

@section('title', 'Manajemen User')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            
            <div class="card-header">
                
                {{-- <h3 class="card-title">Daftar User Sistem</h3> --}}
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Tambah User Baru
                </a>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th style="width: 15%;">Role</th>
                            <th>Klinik</th>
                            <th style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge {{ $user->hasRole('Admin') ? 'bg-danger' : 'bg-primary' }}">
                                        {{ $user->roles->first()->name ?? 'Tanpa Role' }}
                                    </span>
                                </td>
                                <td>
                    {{-- Cek jika user punya relasi klinik dan tampilkan namanya --}}
                    {{ $user->klinik->nama_klinik ?? '-' }}
                </td>
                                <td>
                                    {{-- 1. Perbaiki link Edit --}}
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>

                                    {{-- 2. Tambahkan Form Hapus --}}
                                    {{-- Jangan tampilkan tombol hapus untuk Admin (ID 1) --}}
                                    @if($user->id != 1 && !$user->hasRole('Superadmin'))
    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger btn-delete">
            <i class="fas fa-trash"></i> Hapus
        </button>
    </form>
@endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada data user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
</div>
@endsection

{{-- 3. Tambahkan script SweetAlert untuk konfirmasi Hapus --}}
@push('js')
<script>
    $(function() {
        // Menangani klik pada tombol dengan class .btn-delete
        $('.btn-delete').on('click', function(e) {
            e.preventDefault(); 
            var form = $(this).closest('form'); 

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "User ini akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush