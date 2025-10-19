<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; // <-- Import Role model

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates standard roles: Superadmin, Admin, Staf.
     */
    public function run(): void
    {
        // Gunakan firstOrCreate agar aman dijalankan berkali-kali
        Role::firstOrCreate(['name' => 'Superadmin']);
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Staf']);
    }
}