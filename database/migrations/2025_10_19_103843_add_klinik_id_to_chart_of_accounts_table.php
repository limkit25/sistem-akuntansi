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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Tambahkan kolom klinik_id
            $table->foreignId('klinik_id')
                  ->nullable() // Boleh KOSONG (untuk akun global/default?)
                  ->constrained('kliniks') // Terhubung ke tabel kliniks
                  ->onDelete('cascade') // Jika klinik dihapus, akunnya ikut terhapus
                  ->after('id'); // Letakkan setelah kolom 'id'

            // Hapus unique constraint lama pada kode_akun (karena kode bisa sama antar klinik)
            // Nama constraint mungkin berbeda, cek nama constraint 'unique' di tabel Anda
            // Contoh nama: chart_of_accounts_kode_akun_unique
            // $table->dropUnique('chart_of_accounts_kode_akun_unique');

            // Tambahkan unique constraint baru: kode_akun + klinik_id harus unik
            // Artinya, Klinik A boleh punya '1101', Klinik B juga boleh punya '1101'
            // Tapi Klinik A tidak boleh punya dua '1101'.
            // Akun global (klinik_id=NULL) harus punya kode unik tersendiri.
             $table->unique(['klinik_id', 'kode_akun']);

        });

        // (Opsional tapi direkomendasikan) Update akun yang sudah ada
        // Set semua akun lama menjadi 'global' (klinik_id = NULL) atau milik klinik pertama?
        // Untuk amannya, biarkan NULL dulu.
        // \App\Models\ChartOfAccount::query()->update(['klinik_id' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
             // Hapus unique constraint baru
            $table->dropUnique(['klinik_id', 'kode_akun']);

             // Kembalikan unique constraint lama (jika perlu)
            // $table->unique('kode_akun');

            $table->dropForeign(['klinik_id']);
            $table->dropColumn('klinik_id');
        });
    }
};