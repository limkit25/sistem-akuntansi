@extends('layouts.admin')

@section('title', 'Daftar Bagan Akun (COA)')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Akun Baru
                </a>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Tipe Akun</th>
                            <th>Saldo Normal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan di-looping di sini --}}
                        @forelse ($accounts as $account)
                            <tr>
                                <td>{{ $account->kode_akun }}</td>
                                <td>{{ $account->nama_akun }}</td>
                                <td>{{ $account->tipe_akun }}</td>
                                <td>{{ $account->saldo_normal }}</td>
                                <td>
    <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-sm btn-warning">
        <i class="fas fa-edit"></i> Edit
    </a>
    
    <form action="{{ route('accounts.destroy', $account->id) }}" method="POST" class="d-inline">
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
                                <td colspan="5" class="text-center">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
</div>
@endsection
@push('js')
<script>
    // Pastikan dokumen sudah siap
    $(function() {
        
        // Menangani klik pada tombol dengan class .btn-delete
        $('.btn-delete').on('click', function(e) {
            
            // Mencegah form terkirim langsung
            e.preventDefault(); 
            
            // Simpan referensi ke form
            var form = $(this).closest('form'); 

            // Tampilkan pop up SweetAlert
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang sudah dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Warna tombol hapus (merah)
                cancelButtonColor: '#3085d6', // Warna tombol batal (biru)
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Jika pengguna menekan "Ya, hapus!"
                if (result.isConfirmed) {
                    // Kirim form-nya
                    form.submit();
                }
            });
        });
    });
</script>
@endpush