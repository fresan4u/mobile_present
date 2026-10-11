<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal hari libur: scan ditutup seharian pada tanggal-tanggal ini.
     * Sabtu dan Minggu tidak perlu dicatat (sudah ditutup lewat config hari_aktif).
     */
    public function up(): void
    {
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('keterangan', 150)->nullable(); // contoh: "Libur Nasional"

            // Petugas atau admin yang menambahkan.
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
    }
};