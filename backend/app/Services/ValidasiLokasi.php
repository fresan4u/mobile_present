<?php

namespace App\Services;

/**
 * Memeriksa apakah posisi murid berada dalam radius sekolah. Seluruh perhitungan
 * dilakukan di server; koordinat dari aplikasi dianggap data yang belum tentu jujur
 * dan hanya dipakai bersama batas akurasi dan radius dari config/absensi.php.
 */
class ValidasiLokasi
{
    public const DI_LUAR_RADIUS = 'di_luar_radius';
    public const AKURASI_BURUK = 'akurasi_buruk';
    public const LOKASI_SEKOLAH_BELUM_DIATUR = 'lokasi_sekolah_belum_diatur';

    private const RADIUS_BUMI_METER = 6371000;

    /**
     * @return array{sah: bool, alasan: string|null, jarak_m: int|null, akurasi_m: int}
     */
    public function periksa(float $latitude, float $longitude, float $akurasiMeter): array
    {
        $konfig = config('absensi.lokasi');
        $akurasi = (int) round($akurasiMeter);

        // Pengecekan lokasi dimatikan lewat konfigurasi (misalnya saat pengembangan).
        if (! $konfig['aktif']) {
            return ['sah' => true, 'alasan' => null, 'jarak_m' => null, 'akurasi_m' => $akurasi];
        }

        // Titik tengah sekolah belum diisi: tolak, jangan diam-diam meloloskan semua lokasi.
        if ($konfig['latitude'] === null || $konfig['latitude'] === ''
            || $konfig['longitude'] === null || $konfig['longitude'] === '') {
            return ['sah' => false, 'alasan' => self::LOKASI_SEKOLAH_BELUM_DIATUR, 'jarak_m' => null, 'akurasi_m' => $akurasi];
        }

        $jarak = $this->jarakMeter($latitude, $longitude, (float) $konfig['latitude'], (float) $konfig['longitude']);

        $alasan = null;
        if ($akurasiMeter > $konfig['akurasi_maks_meter']) {
            $alasan = self::AKURASI_BURUK;
        } elseif ($jarak > $konfig['radius_meter']) {
            $alasan = self::DI_LUAR_RADIUS;
        }

        return [
            'sah' => $alasan === null,
            'alasan' => $alasan,
            'jarak_m' => (int) round($jarak),
            'akurasi_m' => $akurasi,
        ];
    }

    /**
     * Jarak dua titik di permukaan bumi dalam meter (rumus Haversine).
     */
    public function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::RADIUS_BUMI_METER * asin(min(1.0, sqrt($a)));
    }
}