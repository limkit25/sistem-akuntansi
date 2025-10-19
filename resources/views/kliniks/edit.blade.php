@extends('layouts.admin')

@section('title', 'Edit Klinik: ' . $klinik->nama_klinik)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning"> {{-- Card warna kuning untuk edit --}}
            <div class="card-header">
                <h3 class="card-title">Formulir Edit Klinik</h3>
            </div>
            {{-- Arahkan ke route update dengan ID klinik --}}
            <form action="{{ route('kliniks.update', $klinik->id) }}" method="POST">
                @csrf
                @method('PUT') {{-- Method PUT untuk update --}}
                <div class="card-body">
                    <div class="form-group">
                        <label for="nama_klinik">Nama Klinik <span class="text-danger">*</span></label>
                        {{-- Isi value dengan data lama --}}
                        <input type="text" class="form-control @error('nama_klinik') is-invalid @enderror" id="nama_klinik" name="nama_klinik" value="{{ old('nama_klinik', $klinik->nama_klinik) }}" required>
                        @error('nama_klinik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="kode_klinik">Kode Klinik (Opsional)</label>
                        <input type="text" class="form-control @error('kode_klinik') is-invalid @enderror" id="kode_klinik" name="kode_klinik" value="{{ old('kode_klinik', $klinik->kode_klinik) }}">
                        @error('kode_klinik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="telepon">Telepon (Opsional)</label>
                        <input type="text" class="form-control @error('telepon') is-invalid @enderror" id="telepon" name="telepon" value="{{ old('telepon', $klinik->telepon) }}">
                        @error('telepon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat (Opsional)</label>
                        <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="3">{{ old('alamat', $klinik->alamat) }}</textarea>
                         @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- Tambahkan pilihan Status Aktif/Non-Aktif --}}
                    <div class="form-group">
                        <label for="is_active">Status <span class="text-danger">*</span></label>
                        <select class="form-control @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
                            <option value="1" {{ old('is_active', $klinik->is_active) == 1 ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ old('is_active', $klinik->is_active) == 0 ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                         @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Update</button> {{-- Tombol Update --}}
                    <a href="{{ route('kliniks.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
        </div>
</div>
@endsection