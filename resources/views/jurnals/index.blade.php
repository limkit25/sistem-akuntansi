@extends('layouts.admin')

@section('title', 'Daftar Jurnal Umum')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('jurnals.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Jurnal Baru
                </a>
            </div>
            <div class="card-body">
                @if($periods->isEmpty())
                    <div class="alert alert-info">
                        Belum ada data jurnal. Silakan <a href="{{ route('jurnals.create') }}">buat jurnal baru</a>.
                    </div>
                @else
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Periode Jurnal</th>
                                <th style="width: 20%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periods as $period)
                                <tr>
                                    <td>
                                        <h4>{{ $period->period_name }}</h4>
                                    </td>
                                    <td>
                                        <a href="{{ route('jurnals.showMonthly', $period->period_value) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            </div>
        </div>
</div>
@endsection