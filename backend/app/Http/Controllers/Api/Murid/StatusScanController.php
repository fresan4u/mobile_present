<?php

namespace App\Http\Controllers\Api\Murid;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Services\AturanScan;
use App\Services\JadwalAbsensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Status scan untuk murid yang sedang login: apakah scan sedang dibuka,
 * apa yang sudah dilakukan hari ini, dan apa yang boleh dilakukan sekarang.
 */
class StatusScanController extends Controller
{
    public function __invoke(Request $request, JadwalAbsensi $jadwal, AturanScan $aturan): JsonResponse
    {
        // Waktu selalu dari server, tidak pernah dari kiriman aplikasi.
        $scan = $jadwal->tentukan();

        $absensi = Absensi::where('user_id', $request->user()->id)
            ->whereDate('tanggal', $scan['tanggal'])
            ->first();

        [$bisaScan, $alasan] = $aturan->periksa($scan, $absensi);

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
}