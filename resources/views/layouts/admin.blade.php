<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Akuntansi')</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">

    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">

    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    @stack('css')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile.edit') }}" role="button">
                    <i class="fas fa-user-circle"></i> {{ Auth::user()->name ?? 'Pengguna' }}
                    {{-- Tampilkan Role --}}
                    <span class="badge badge-info ml-1">{{ Auth::user()->roles->first()->name ?? '' }}</span>
                </a>
            </li>
        </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-text font-weight-light">Sistem Akuntansi</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                    {{-- Dashboard (Semua Role) --}}
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    {{-- Data Master (Superadmin & Admin) --}}
                    @hasanyrole('Superadmin|Admin')
                    <li class="nav-header">DATA MASTER</li>
                    @endhasanyrole

                    {{-- COA (Superadmin & Admin) --}}
                    @hasanyrole('Superadmin|Admin')
                    <li class="nav-item">
                        <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->is('accounts*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i>
                            <p>Bagan Akun (COA)</p>
                        </a>
                    </li>
                    @endhasanyrole

                    {{-- Manajemen User & Klinik (Hanya Superadmin) --}}
                    @role('Superadmin')
                    <li class="nav-item">
                        <a href="{{ route('kliniks.index') }}" class="nav-link {{ request()->is('kliniks*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hospital-alt"></i>
                            <p>Manajemen Klinik</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>Manajemen User</p>
                        </a>
                    </li>
                    
                    @endrole

                    {{-- Transaksi (Semua Role) --}}
                    @hasanyrole('Superadmin|Admin|Staf')
                    <li class="nav-header">TRANSAKSI</li>
                    <li class="nav-item">
                        <a href="{{ route('jurnals.index') }}" class="nav-link {{ request()->is('jurnals*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-pen-square"></i>
                            <p>Jurnal Umum</p>
                        </a>
                    </li>
                    @endhasanyrole

                    {{-- Laporan (Superadmin & Admin) --}}
                    @hasanyrole('Superadmin|Admin')
                    <li class="nav-header">LAPORAN</li>
                    <li class="nav-item">
                        <a href="{{ route('laporan.bukuBesar') }}" class="nav-link {{ request()->is('laporan/buku-besar*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-folder"></i>
                            <p>Buku Besar</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('laporan.neracaSaldo') }}" class="nav-link {{ request()->is('laporan/neraca-saldo*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-balance-scale"></i>
                            <p>Neraca Saldo</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('laporan.labaRugi') }}" class="nav-link {{ request()->is('laporan/laba-rugi*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Laporan Laba Rugi</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('laporan.neraca') }}" class="nav-link {{ request()->is('laporan/neraca*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-landmark"></i>
                            <p>Laporan Neraca</p>
                        </a>
                    </li>
                    @endhasanyrole

                    {{-- Pengaturan (Semua Role) --}}
                    <li class="nav-header">PENGATURAN</li>
                    <li class="nav-item">
                        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->is('profile*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-edit"></i>
                            <p>Profil Saya</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
                            <p class="text">Logout</p>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </nav>
            </div>
        </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>@yield('title')</h1>
                    </div>
                </div>
            </div></section>

        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
        </div>
    <footer class="main-footer">
        <strong>Copyright &copy; 2024-{{ date('Y') }} <a href="#">Sistem Akuntansi</a>.</strong> All rights reserved.
    </footer>

</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>

<script src="{{ asset('dist/js/adminlte.min.js') }}"></script>

<script>
    $(function() {
        // Setting default Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };

        // Menampilkan notifikasi 'success'
        @if (session('success'))
            toastr.success('{{ session('success') }}');
        @endif

        // Menampilkan notifikasi 'error'
        @if (session('error'))
            toastr.error('{{ session('error') }}');
        @endif

        // Menampilkan notifikasi error validasi
        @if ($errors->any())
            // Tampilkan hanya satu pesan error umum jika ada banyak error
             toastr.error('Terdapat kesalahan validasi pada input Anda.');
            // Jika Anda ingin menampilkan setiap error (bisa terlalu banyak):
            // @foreach ($errors->all() as $error)
            //     toastr.error('{{ $error }}');
            // @endforeach
        @endif
    });
</script>

@stack('js')
</body>
</html>