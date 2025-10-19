@extends('layouts.admin')

@section('title', 'Edit Akun: ' . $account->nama_akun) @section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning"> <div class="card-header">
                <h3 class="card-title">Formulir Edit Akun</h3>
            </div>
            

            <form action="{{ route('accounts.update', $account->id) }}" method="POST">
                @csrf
                @method('PUT') <div class="card-body">
                    <div class="form-group">
                        <label for="kode_akun">Kode Akun</label>
                        <input type="text" class="form-control" id="kode_akun" name="kode_akun" placeholder="Contoh: 1101" value="{{ old('kode_akun', $account->kode_akun) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_akun">Nama Akun</label>
                        <input type="text" class="form-control" id="nama_akun" name="nama_akun" placeholder="Contoh: Kas" value="{{ old('nama_akun', $account->nama_akun) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="tipe_akun">Tipe Akun</label>
                        <select class="form-control" id="tipe_akun" name="tipe_akun" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="Aset" {{ old('tipe_akun', $account->tipe_akun) == 'Aset' ? 'selected' : '' }}>Aset</option>
                            <option value="Liabilitas" {{ old('tipe_akun', $account->tipe_akun) == 'Liabilitas' ? 'selected' : '' }}>Liabilitas</option>
                            <option value="Ekuitas" {{ old('tipe_akun', $account->tipe_akun) == 'Ekuitas' ? 'selected' : '' }}>Ekuitas</option>
                            <option value="Pendapatan" {{ old('tipe_akun', $account->tipe_akun) == 'Pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                            <option value="Biaya" {{ old('tipe_akun', $account->tipe_akun) == 'Biaya' ? 'selected' : '' }}>Biaya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="saldo_normal">Saldo Normal</label>
                        <select class="form-control" id="saldo_normal" name="saldo_normal" required>
                            <option value="">-- Pilih Saldo Normal --</option>
                            <option value="Debit" {{ old('saldo_normal', $account->saldo_normal) == 'Debit' ? 'selected' : '' }}>Debit</option>
                            <option value="Kredit" {{ old('saldo_normal', $account->saldo_normal) == 'Kredit' ? 'selected' : '' }}>Kredit</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Update</button> <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
        </div>
</div>
@endsection