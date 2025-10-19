<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role; // <-- Tetap import Role
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cari atau buat user Superadmin
        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'], // Ganti email jika perlu
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // Ganti password!
                'klinik_id' => null
            ]
        );

        // 2. Tetapkan role 'Superadmin' ke user tersebut
        // Pastikan role 'Superadmin' sudah dibuat oleh RoleSeeder
        $user->assignRole('Superadmin');
    }
}