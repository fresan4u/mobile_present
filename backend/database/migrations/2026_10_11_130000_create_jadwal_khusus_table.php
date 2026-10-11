<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal khusus untuk SATU tanggal tertentu (misalnya pulang lebih awal).
     * Kolom jam yang dikosongkan berarti memakai jam bawaan dari config/absensi.php.
     */
    public function up(): void
    {
        Schema::create('jadwal_khusus', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();

            // Jam scan masuk. Kosong = pakai jam bawaan.
            $table->time('masuk_buka')->nullable();
            $table->time('masuk_batas_hadir')->nullable();
            $table->time('masuk_tutup')->nullable();

            // Jam scan pulang. Kosong = pakai jam bawaan.
            $table->time('pulang_buka')->nullable();
            $table->time('pulang_tutup')->nullable();

            $table->string('keterangan', 150)->nullable(); // contoh: "Rapat guru, pulang lebih awal"

            // Petugas yang membuat atau terakhir mengubah.
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_khusus');
    }
};