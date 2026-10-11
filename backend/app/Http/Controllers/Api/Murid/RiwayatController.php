<?php

namespace App\Http\Controllers\Api\Murid;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Riwayat absensi murid yang sedang login: daftar per tanggal (terbaru dulu)
 * dan rekap jumlah per status untuk seluruh jangkauan tanggal.
 * Kueri selalu dikunci ke akun yang login, jadi murid hanya melihat datanya sendiri.
 */
class RiwayatController extends Controller
{
    private const BATAS_HARI = 366;

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Bawaan: 30 hari terakhir (termasuk hari ini), dihitung dengan jam server WIB.
        $zona = config('absensi.timezone');
        $hariIni = now($zona)->startOfDay();

        $sampai = isset($data['sampai'])
            ? Carbon::createFromFormat('Y-m-d', $data['sampai'], $zona)->startOfDay()
            : $hariIni->copy();
        $dari = isset($data['dari'])
            ? Carbon::createFromFormat('Y-m-d', $data['dari'], $zona)->startOfDay()
            : $sampai->copy()->subDays(29);

        if ($dari->greaterThan($sampai)) {
            return response()->json([
                'message' => 'Tanggal awal tidak boleh setelah tanggal akhir.',
            ], 422);
        }

        if ($dari->diffInDays($sampai) > self::BATAS_HARI) {
            return response()->json([
                'message' => 'Jangkauan tanggal maksimal '.self::BATAS_HARI.' hari.',
            ], 422);
        }

        // whereDate (bukan whereBetween) agar hasilnya sama di MySQL dan SQLite.
        $kueri = Absensi::where('user_id', $request->user()->id)
            ->whereDate('tanggal', '>=', $dari->toDateString())
            ->whereDate('tanggal', '<=', $sampai->toDateString());

        // Rekap dihitung dari seluruh jangkauan, bukan hanya dari satu halaman.
        $hitung = (clone $kueri)
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $rekap = [];
        foreach ([
            Absensi::STATUS_HADIR,
            Absensi::STATUS_TERLAMBAT,
            Absensi::STATUS_SAKIT,
            Absensi::STATUS_IZIN,
            Absensi::STATUS_TIDAK_HADIR,
        ] as $status) {
            $rekap[$status] = (int) ($hitung[$status] ?? 0);
        }

        $halaman = $kueri
            ->orderByDesc('tanggal')
            ->paginate($data['per_page'] ?? 30);

        return response()->json([
            'jangkauan' => [
                'dari' => $dari->toDateString(),
                'sampai' => $sampai->toDateString(),
            ],
            'rekap' => $rekap,
            'data' => $halaman->getCollection()->map(fn (Absensi $absensi) => [
                'tanggal' => $absensi->tanggal->toDateString(),
                'jam_masuk' => $absensi->jam_masuk,
                'jam_pulang' => $absensi->jam_pulang,
                'status' => $absensi->status,
                'metode_masuk' => $absensi->metode_masuk,
                'metode_pulang' => $absensi->metode_pulang,
                'catatan' => $absensi->catatan,
            ])->values(),
            'halaman' => [
                'saat_ini' => $halaman->currentPage(),
                'terakhir' => $halaman->lastPage(),
                'per_halaman' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
        ]);
    }
}