@extends('layouts.admin')

@section('title', 'Bagan Akun (COA)')

{{-- CSS for Select2 --}}
@push('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        {{-- CARD FILTER (Hanya Superadmin) --}}
        @role('Superadmin')
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Akun</h3>
            </div>
            <form action="{{ route('accounts.index') }}" method="GET">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="klinik_id">Tampilkan Akun Klinik:</label>
                                <select class="form-control select2bs4" id="klinik_id" name="klinik_id">
                                    <option value="">-- Semua Klinik & Global --</option>
                                    <option value="global" {{ $selectedKlinikId === 'global' ? 'selected' : '' }}>-- Akun Global (Tanpa Klinik) --</option>
                                    @foreach ($kliniks as $klinik)
                                        <option value="{{ $klinik->id }}"
                                            {{ $selectedKlinikId == $klinik->id ? 'selected' : '' }}>
                                            {{ $klinik->nama_klinik }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @else
         {{-- Info untuk Admin Klinik --}}
        <div class="alert alert-info">
            Menampilkan akun untuk klinik: <strong>{{ Auth::user()->klinik->nama_klinik ?? 'Global' }}</strong>
        </div>
        @endrole
        {{-- CARD DAFTAR AKUN --}}
        <div class="card">
            <div class="card-header">
                {{-- Tombol Tambah bisa diakses Superadmin & Admin Klinik (jika punya klinik) --}}
                 @if(Auth::user()->hasRole('Superadmin') || Auth::user()->klinik_id)
                <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Akun Baru
                </a>
                 @else
                 <div class="alert alert-warning p-2">Anda belum terhubung ke klinik untuk menambah akun.</div>
                 @endif
            </div>
            <div class="card-body">
                <table id="account-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Klinik</th>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Tipe</th>
                            <th>Saldo Normal</th>
                            <th style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr>
                                <td>
                                    @if($account->klinik)
                                        <span class="badge bg-info">{{ $account->klinik->nama_klinik }}</span>
                                    @else
                                        <span class="badge bg-secondary">Global</span>
                                    @endif
                                </td>
                                <td>{{ $account->kode_akun }}</td>
                                <td>{{ $account->nama_akun }}</td>
                                <td>{{ $account->tipe_akun }}</td>
                                <td>{{ $account->saldo_normal }}</td>
                                <td>
                                    {{-- Cek otorisasi sebelum menampilkan tombol --}}
                                    @php
                                        $canEdit = Auth::user()->hasRole('Superadmin') || ($account->klinik_id == Auth::user()->klinik_id);
                                        $canDelete = Auth::user()->hasRole('Superadmin') || ($account->klinik_id == Auth::user()->klinik_id && $account->klinik_id !== null); // Admin tidak bisa hapus global
                                    @endphp

                                    @if($canEdit)
                                    <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-xs btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif

                                    @if($canDelete)
                                    <form action="{{ route('accounts.destroy', $account->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    @if(Auth::user()->hasRole('Superadmin') && $selectedKlinikId)
                                        Tidak ada data akun untuk filter yang dipilih.
                                    @elseif(!Auth::user()->hasRole('Superadmin') && !Auth::user()->klinik_id)
                                         Anda belum terhubung ke klinik.
                                    @else
                                         Belum ada data akun.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
</div>
@endsection

{{-- JS untuk Select2 dan SweetAlert --}}
@push('js')
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    $(function() {
        // Init Select2
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        // Init DataTable (Opsional, untuk search & sort)
        // Jika Anda ingin tabelnya bisa dicari/diurutkan
        // $('#account-table').DataTable({
        //     "paging": true,
        //     "lengthChange": false,
        //     "searching": true,
        //     "ordering": true,
        //     "info": true,
        //     "autoWidth": false,
        //     "responsive": true,
        // });

        // Init SweetAlert for Delete
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Akun ini akan dihapus. Pastikan tidak ada transaksi yang menggunakannya.",
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
{{-- Jika Anda pakai DataTable, tambahkan JS-nya --}}
{{-- <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}"> --}}
{{-- <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script> --}}
{{-- <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script> --}}
@endpush