<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu murid pada satu tanggal.
     * Murid yang tidak scan tidak punya baris, kecuali petugas mengoreksi manual.
     */
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');

            // Waktu scan, dicatat oleh server. Kosong bila belum scan.
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();

            // hadir, terlambat (ditentukan sistem); sakit, izin, tidak_hadir (diinput petugas).
            $table->string('status', 20)->nullable()->index();

            // face_recognition atau fingerprint.
            $table->string('metode_masuk', 30)->nullable();
            $table->string('metode_pulang', 30)->nullable();

            // Bukti foto dari kamera saat scan (path file).
            $table->string('foto_masuk')->nullable();
            $table->string('foto_pulang')->nullable();

            // Data lokasi saat scan, disimpan agar petugas bisa mengaudit.
            $table->decimal('latitude_masuk', 10, 7)->nullable();
            $table->decimal('longitude_masuk', 10, 7)->nullable();
            $table->unsignedSmallInteger('jarak_masuk_m')->nullable();
            $table->unsignedSmallInteger('akurasi_masuk_m')->nullable();

            $table->decimal('latitude_pulang', 10, 7)->nullable();
            $table->decimal('longitude_pulang', 10, 7)->nullable();
            $table->unsignedSmallInteger('jarak_pulang_m')->nullable();
            $table->unsignedSmallInteger('akurasi_pulang_m')->nullable();

            // Catatan petugas dan siapa yang terakhir mengoreksi.
            $table->text('catatan')->nullable();
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Satu murid hanya boleh punya satu baris per tanggal.
            $table->unique(['user_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};