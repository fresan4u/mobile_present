<?php

namespace App\Http\Controllers\Api\Murid;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Services\AturanScan;
use App\Services\JadwalAbsensi;
use App\Services\ValidasiLokasi;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Scan masuk dan scan pulang murid. Jam, sesi, dan status ditentukan server;
 * yang dikirim aplikasi hanya metode, koordinat, akurasi GPS, dan foto.
 */
class ScanController extends Controller
{
    private const PESAN = [
        'sudah_masuk' => 'Kamu sudah tercatat masuk hari ini.',
        'sudah_pulang' => 'Kamu sudah tercatat pulang hari ini.',
        'belum_masuk' => 'Kamu belum tercatat masuk. Temui petugas piket.',
        'diatur_petugas' => 'Status kehadiranmu hari ini diatur oleh petugas.',
        'hari_libur' => 'Hari ini libur, absensi tidak dibuka.',
        'akhir_pekan' => 'Absensi tidak dibuka di akhir pekan.',
        'di_luar_jam' => 'Absensi sedang tidak dibuka.',
        'di_luar_radius' => 'Kamu berada di luar area sekolah.',
        'akurasi_buruk' => 'Sinyal GPS kurang akurat. Pindah ke tempat terbuka lalu coba lagi.',
        'lokasi_sekolah_belum_diatur' => 'Lokasi sekolah belum diatur. Hubungi petugas.',
    ];

    public function __invoke(
        Request $request,
        JadwalAbsensi $jadwal,
        AturanScan $aturan,
        ValidasiLokasi $lokasi,
    ): JsonResponse {
        $data = $request->validate([
            'metode' => ['required', 'string', 'in:'.Absensi::METODE_FACE.','.Absensi::METODE_FINGERPRINT],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['required', 'numeric', 'min:0', 'max:100000'],
            'foto' => ['required_if:metode,'.Absensi::METODE_FACE, 'nullable', 'mimes:jpeg,jpg,png', 'max:2048'],
        ]);

        $user = $request->user();

        // Waktu selalu dari server, tidak pernah dari kiriman aplikasi.
        $scan = $jadwal->tentukan();
        $waktu = Carbon::parse($scan['waktu_server']);

        try {
            return DB::transaction(function () use ($request, $data, $user, $scan, $waktu, $aturan, $lokasi) {
                // Kunci baris murid hari ini supaya dua permintaan bersamaan tidak saling menimpa.
                $absensi = Absensi::where('user_id', $user->id)
                    ->whereDate('tanggal', $scan['tanggal'])
                    ->lockForUpdate()
                    ->first();

                [$bisaScan, $alasan] = $aturan->periksa($scan, $absensi);
                if (! $bisaScan) {
                    return $this->tolak($alasan);
                }

                $cek = $lokasi->periksa((float) $data['latitude'], (float) $data['longitude'], (float) $data['akurasi']);
                if (! $cek['sah']) {
                    return $this->tolak($cek['alasan'], [
                        'jarak_m' => $cek['jarak_m'],
                        'akurasi_m' => $cek['akurasi_m'],
                    ]);
                }

                // Foto hanya disimpan untuk scan wajah, di disk privat (tanpa alamat web).
                $foto = $data['metode'] === Absensi::METODE_FACE ? $request->file('foto') : null;
                $path = $foto ? $foto->store('absensi/'.$scan['tanggal'], 'local') : null;

                $sesi = $scan['sesi']; // "masuk" atau "pulang"
                $atribut = [
                    'jam_'.$sesi => $waktu->format('H:i:s'),
                    'metode_'.$sesi => $data['metode'],
                    'foto_'.$sesi => $path,
                    'latitude_'.$sesi => $data['latitude'],
                    'longitude_'.$sesi => $data['longitude'],
                    'jarak_'.$sesi.'_m' => $cek['jarak_m'],
                    'akurasi_'.$sesi.'_m' => $cek['akurasi_m'],
                ];

                if ($sesi === JadwalAbsensi::SESI_MASUK) {
                    $atribut['status'] = $scan['status_masuk'];
                }

                $absensi ??= new Absensi(['tanggal' => $scan['tanggal']]);
                $absensi->user_id = $user->id;

                try {
                    $absensi->forceFill($atribut)->save();
                } catch (Throwable $e) {
                    if ($path) {
                        Storage::disk('local')->delete($path);
                    }
                    throw $e;
                }

                return response()->json([
                    'message' => $sesi === JadwalAbsensi::SESI_MASUK
                        ? 'Absensi masuk berhasil dicatat.'
                        : 'Absensi pulang berhasil dicatat.',
                    'sesi' => $sesi,
                    'status' => $absensi->status,
                    'jam' => $waktu->format('H:i:s'),
                    'jarak_m' => $cek['jarak_m'],
                    'akurasi_m' => $cek['akurasi_m'],
                    'absensi_hari_ini' => [
                        'tanggal' => $scan['tanggal'],
                        'jam_masuk' => $absensi->jam_masuk,
                        'jam_pulang' => $absensi->jam_pulang,
                        'status' => $absensi->status,
                        'metode_masuk' => $absensi->metode_masuk,
                        'metode_pulang' => $absensi->metode_pulang,
                    ],
                ], 201);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            // Permintaan lain untuk murid dan tanggal yang sama menyimpan lebih dulu.
            return $this->tolak('sudah_masuk');
        }
    }

    /**
     * @param  array<string, mixed>  $tambahan
     */
    private function tolak(string $alasan, array $tambahan = []): JsonResponse
    {
        return response()->json(array_merge([
            'message' => self::PESAN[$alasan] ?? 'Scan tidak dapat dilakukan.',
            'alasan' => $alasan,
        ], $tambahan), 422);
    }
}