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
    </div>
@endsection