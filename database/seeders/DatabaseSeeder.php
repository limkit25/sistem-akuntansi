<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class, // <-- Panggil RoleSeeder DULU
            SuperadminSeeder::class,
            // KlinikSeeder::class, // Jika Anda buat seeder Klinik, panggil di sini
        ]);
    }
}