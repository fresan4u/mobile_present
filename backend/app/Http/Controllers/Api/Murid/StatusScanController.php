<?php

namespace App\Http\Controllers\Api\Murid;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Services\JadwalAbsensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Status scan untuk murid yang sedang login: apakah scan sedang dibuka,
 * apa yang sudah dilakukan hari ini, dan apa yang boleh dilakukan sekarang.
 */
class StatusScanController extends Controller
{
    public function __invoke(Request $request, JadwalAbsensi $jadwal): JsonResponse
    {
        // Waktu selalu dari server, tidak pernah dari kiriman aplikasi.
        $scan = $jadwal->tentukan();

        $absensi = Absensi::where('user_id', $request->user()->id)
            ->whereDate('tanggal', $scan['tanggal'])
            ->first();

        [$bisaScan, $alasan] = $this->tentukanBisaScan($scan, $absensi);

        return response()->json([
            'scan' => $scan,
            'absensi_hari_ini' => $absensi ? [
                'tanggal' => $absensi->tanggal->toDateString(),
                'jam_masuk' => $absensi->jam_masuk,
                'jam_pulang' => $absensi->jam_pulang,
                'status' => $absensi->status,
                'metode_masuk' => $absensi->metode_masuk,
                'metode_pulang' => $absensi->metode_pulang,
                'catatan' => $absensi->catatan,
            ] : null,
            'bisa_scan' => $bisaScan,
            'alasan' => $alasan, // kosong bila bisa_scan = true
        ]);
    }

    /**
     * @param  array<string, mixed>  $scan
     * @return array{0: bool, 1: string|null}
     */
    private function tentukanBisaScan(array $scan, ?Absensi $absensi): array
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