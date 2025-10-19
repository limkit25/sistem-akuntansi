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
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom klinik_id
            $table->foreignId('klinik_id')
                  ->nullable() // Boleh kosong (misal untuk Admin)
                  ->constrained('kliniks') // Terhubung ke tabel kliniks
                  ->onDelete('set null') // Jika klinik dihapus, set user.klinik_id jadi NULL
                  ->after('id'); // Letakkan setelah kolom 'id'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['klinik_id']);
            $table->dropColumn('klinik_id');
        });
    }
};