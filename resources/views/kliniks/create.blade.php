@extends('layouts.admin')

@section('title', 'Tambah Klinik Baru')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Formulir Klinik Baru</h3>
            </div>
            <form action="{{ route('kliniks.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="nama_klinik">Nama Klinik <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_klinik" name="nama_klinik" placeholder="Contoh: Klinik Sehat" value="{{ old('nama_klinik') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="kode_klinik">Kode Klinik (Opsional)</label>
                        <input type="text" class="form-control" id="kode_klinik" name="kode_klinik" placeholder="Contoh: KL-A (Harus unik)" value="{{ old('kode_klinik') }}">
                    </div>
                    <div class="form-group">
                        <label for="telepon">Telepon (Opsional)</label>
                        <input type="text" class="form-control" id="telepon" name="telepon" value="{{ old('telepon') }}">
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat (Opsional)</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3">{{ old('alamat') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('kliniks.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
        </div>
</div>
@endsection