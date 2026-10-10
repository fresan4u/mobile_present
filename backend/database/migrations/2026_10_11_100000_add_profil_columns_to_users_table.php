<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom peran dan data murid pada tabel users.
     * Satu tabel dipakai bersama: murid, petugas, dan admin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Peran akun: siswa, petugas, atau admin.
            $table->string('role', 20)->default('siswa')->after('password')->index();

            // Data murid. Kosong untuk petugas dan admin.
            $table->string('nis', 30)->nullable()->unique()->after('role');
            $table->string('nisn', 30)->nullable()->unique()->after('nis');
            $table->string('kelas', 20)->nullable()->index()->after('nisn'); // contoh: "X 10.1"

            // Path foto wajah murid (dipakai untuk pengenalan wajah nanti).
            $table->string('foto')->nullable()->after('kelas');

            // Murid login memakai NIS, jadi email boleh kosong.
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['kelas']);
            $table->dropUnique(['nis']);
            $table->dropUnique(['nisn']);
            $table->dropColumn(['role', 'nis', 'nisn', 'kelas', 'foto']);

            // Catatan: mengembalikan email menjadi wajib gagal bila sudah ada baris tanpa email.
            $table->string('email')->nullable(false)->change();
        });
    }
};