<?php

namespace App\Services;

use App\Models\Absensi;

/**
 * Satu-satunya tempat aturan "apa yang boleh dilakukan murid sekarang".
 * Dipakai oleh endpoint status scan (untuk menampilkan) dan endpoint scan
 * (untuk memutuskan), supaya keduanya tidak pernah berbeda pendapat.
 */
class AturanScan
{
    /**
     * @param  array<string, mixed>  $scan  Hasil JadwalAbsensi::tentukan()
     * @return array{0: bool, 1: string|null}  [bisa scan, alasan bila tidak bisa]
     */
    public function periksa(array $scan, ?Absensi $absensi): array
    {
        // Tercatat hadir = sudah scan masuk, atau petugas sudah mengisi status
        // Hadir/Terlambat (misalnya murid yang terlambat sekali dan diurus petugas piket).
        $tercatatHadir = $absensi !== null && (
            $absensi->jam_masuk !== null
            || in_array($absensi->status, [Absensi::STATUS_HADIR, Absensi::STATUS_TERLAMBAT], true)
        );

        // Sakit atau Izin: diatur petugas, murid tidak perlu dan tidak bisa scan.
        $diaturPetugas = $absensi !== null && in_array($absensi->status, [Absensi::STATUS_SAKIT, Absensi::STATUS_IZIN], true);

        return match ($scan['sesi']) {
            JadwalAbsensi::SESI_MASUK => match (true) {
                $tercatatHadir => [false, 'sudah_masuk'],
                $diaturPetugas => [false, 'diatur_petugas'],
                default => [true, null],
            },
            JadwalAbsensi::SESI_PULANG => match (true) {
                $diaturPetugas => [false, 'diatur_petugas'],
                ! $tercatatHadir => [false, 'belum_masuk'],
                $absensi->jam_pulang !== null => [false, 'sudah_pulang'],
                default => [true, null],
            },
            default => [false, $scan['alasan_tutup']],
        };
    }
}