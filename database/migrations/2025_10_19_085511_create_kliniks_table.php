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
        Schema::create('kliniks', function (Blueprint $table) {
            $table->id();
            $table->string('nama_klinik');
            $table->string('kode_klinik', 10)->unique()->nullable(); // Kode unik opsional (misal: KL-A)
            $table->text('alamat')->nullable();
            $table->string('telepon', 20)->nullable();
            $table->boolean('is_active')->default(true); // Status (aktif/non-aktif)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kliniks');
    }
};
