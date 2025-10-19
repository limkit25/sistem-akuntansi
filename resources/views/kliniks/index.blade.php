@extends('layouts.admin')

@section('title', 'Manajemen Klinik')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('kliniks.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Klinik Baru
                </a>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Klinik</th>
                            <th>Telepon</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kliniks as $klinik)
                            <tr>
                                <td>{{ $klinik->kode_klinik ?? '-' }}</td>
                                <td>{{ $klinik->nama_klinik }}</td>
                                <td>{{ $klinik->telepon ?? '-' }}</td>
                                <td>{{ $klinik->alamat ?? '-' }}</td>
                                <td>
                                    @if($klinik->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger">Non-Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('kliniks.edit', $klinik->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('kliniks.destroy', $klinik->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger btn-delete">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data klinik.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
</div>
@endsection

{{-- Script SweetAlert untuk konfirmasi Hapus --}}
@push('js')
<script>
    $(function() {
        $('.btn-delete').on('click', function(e) {
            e.preventDefault(); 
            var form = $(this).closest('form'); 

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data klinik ini akan dihapus permanen!",
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