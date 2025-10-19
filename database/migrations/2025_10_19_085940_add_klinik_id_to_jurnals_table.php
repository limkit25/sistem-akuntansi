<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jurnals', function (Blueprint $table) {
            // 1. Tambahkan kolom 'klinik_id'
            $table->foreignId('klinik_id')
                  ->nullable() // Kita buat nullable dulu
                  ->constrained('kliniks') // Terhubung ke tabel 'kliniks'
                  ->onDelete('restrict') // Mencegah hapus klinik jika sudah ada jurnal
                  ->after('id'); // Letakkan kolomnya setelah 'id'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnals', function (Blueprint $table) {
            // 1. Hapus foreign key constraint
            $table->dropForeign(['klinik_id']);
            // 2. Hapus kolomnya
            $table->dropColumn('klinik_id');
        });
    }
};