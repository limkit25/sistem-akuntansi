@extends('layouts.admin')

@section('title', 'Profil Saya')

@section('content')
<div class="container-fluid">
    <div class="row">
        
        {{-- Kolom Kiri: Update Info & Password --}}
        <div class="col-lg-7">
            
            {{-- Card 1: Update Profile Info --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Informasi Profil</h3>
                </div>
                <div class="card-body">
                    {{-- File ini adalah bawaan Breeze, kita panggil saja --}}
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- Card 2: Update Password --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Ubah Password</h3>
                </div>
                <div class="card-body">
                    {{-- File ini adalah bawaan Breeze --}}
                    @include('profile.partials.update-password-form')
                </div>
            </div>

        </div>

        {{-- Kolom Kanan: Hapus Akun --}}
        <div class="col-lg-5">
            {{-- Card 3: Delete Account --}}
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title">Hapus Akun</h3>
                </div>
                <div class="card-body">
                    {{-- File ini adalah bawaan Breeze --}}
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>

    </div>
</div>
@endsection