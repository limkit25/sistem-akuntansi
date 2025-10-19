@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    
    <div class="row">
        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>Data Master</h3>
                    <p>Bagan Akun (COA)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
                <a href="{{ route('accounts.index') }}" class="small-box-footer">
                    Lihat & Kelola <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Transaksi</h3>
                    <p>Jurnal Umum</p>
                </div>
                <div class="icon">
                    <i class="fas fa-pen-square"></i>
                </div>
                <a href="{{ route('jurnals.index') }}" class="small-box-footer">
                    Lihat & Kelola <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="row">
        <h5 class="col-12 mb-2 mt-3">Laporan Akuntansi</h5>
        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>Buku Besar</h3>
                    <p>Lihat mutasi per akun</p>
                </div>
                <div class="icon">
                    <i class="fas fa-folder"></i>
                </div>
                <a href="{{ route('laporan.bukuBesar') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>Neraca Saldo</h3>
                    <p>Cek keseimbangan akun</p>
                </div>
                <div class="icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <a href="{{ route('laporan.neracaSaldo') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="row">
        <h5 class="col-12 mb-2 mt-3">Laporan Keuangan</h5>
        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>Laba Rugi</h3>
                    <p>Laporan Pendapatan & Biaya</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <a href="{{ route('laporan.labaRugi') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>Neraca</h3>
                    <p>Laporan Aset & Kewajiban</p>
                </div>
                <div class="icon">
                    <i class="fas fa-landmark"></i>
                </div>
                <a href="{{ route('laporan.neraca') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    </div>
@endsection