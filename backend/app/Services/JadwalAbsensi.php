<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\JadwalKhusus;
use Carbon\CarbonInterface;

/**
 * Menentukan apakah scan absensi sedang dibuka, dan bila scan masuk,
 * apakah statusnya hadir atau terlambat. Hanya menilai waktu dan kalender;
 * pengecekan lokasi dan "sudah scan atau belum" dilakukan di tempat lain.
 */
class JadwalAbsensi
{
    public const SESI_TUTUP = 'tutup';
    public const SESI_MASUK = 'masuk';
    public const SESI_PULANG = 'pulang';

    public const TUTUP_HARI_LIBUR = 'hari_libur';
    public const TUTUP_AKHIR_PEKAN = 'akhir_pekan';
    public const TUTUP_DI_LUAR_JAM = 'di_luar_jam';

    /**
     * @param  CarbonInterface|null  $waktu  Kosong = sekarang (jam server). Isi hanya untuk pengujian.
     * @return array<string, mixed>
     */
    public function tentukan(?CarbonInterface $waktu = null): array
    {
        $zona = config('absensi.timezone');
        $waktu = ($waktu ?? now())->copy()->setTimezone($zona);
        $tanggal = $waktu->copy()->startOfDay();

        $libur = HariLibur::whereDate('tanggal', $tanggal)->first();
        $khusus = JadwalKhusus::whereDate('tanggal', $tanggal)->first();

        // Kolom jam yang kosong di jadwal khusus berarti memakai jam bawaan.
        $jadwal = [
            'masuk' => [
                'buka' => $this->jam($khusus?->masuk_buka, config('absensi.masuk.buka')),
                'batas_hadir' => $this->jam($khusus?->masuk_batas_hadir, config('absensi.masuk.batas_hadir')),
                'tutup' => $this->jam($khusus?->masuk_tutup, config('absensi.masuk.tutup')),
            ],
            'pulang' => [
                'buka' => $this->jam($khusus?->pulang_buka, config('absensi.pulang.buka')),
                'tutup' => $this->jam($khusus?->pulang_tutup, config('absensi.pulang.tutup')),
            ],
        ];

        $hasil = [
            'tanggal' => $tanggal->toDateString(),
            'waktu_server' => $waktu->toIso8601String(),
            'sesi' => self::SESI_TUTUP,
            'status_masuk' => null,
            'alasan_tutup' => null,
            'keterangan' => $khusus?->keterangan,
            'jadwal' => $jadwal,
        ];

        // 1. Hari libur: ditutup seharian.
        if ($libur) {
            $hasil['alasan_tutup'] = self::TUTUP_HARI_LIBUR;
            $hasil['keterangan'] = $libur->keterangan;

            return $hasil;
        }

        // 2. Di luar hari aktif (Sabtu dan Minggu): ditutup seharian.
        if (! in_array($waktu->dayOfWeekIso, config('absensi.hari_aktif'), true)) {
            $hasil['alasan_tutup'] = self::TUTUP_AKHIR_PEKAN;

            return $hasil;
        }

        // 3. Jam scan masuk. Batas jam dihitung sampai akhir menit.
        $masuk = $jadwal['masuk'];
        if ($this->dalamJendela($waktu, $tanggal, $masuk['buka'], $masuk['tutup'])) {
            $hasil['sesi'] = self::SESI_MASUK;
            $hasil['status_masuk'] = $waktu->lessThanOrEqualTo($this->akhirMenit($tanggal, $masuk['batas_hadir']))
                ? Absensi::STATUS_HADIR
                : Absensi::STATUS_TERLAMBAT;

            return $hasil;
        }

        // 4. Jam scan pulang.
        $pulang = $jadwal['pulang'];
        if ($this->dalamJendela($waktu, $tanggal, $pulang['buka'], $pulang['tutup'])) {
            $hasil['sesi'] = self::SESI_PULANG;

            return $hasil;
        }

        $hasil['alasan_tutup'] = self::TUTUP_DI_LUAR_JAM;

        return $hasil;
    }

    /** Ambil "HH:MM" dari nilai jadwal khusus, atau pakai nilai bawaan bila kosong. */
    private function jam(?string $nilai, string $bawaan): string
    {
        return substr($nilai ?: $bawaan, 0, 5);
    }

    /**
     * Apakah $waktu berada di antara jam buka dan jam tutup (sampai akhir menit tutup).
     * Jendela yang terbalik (buka lebih besar dari tutup) dianggap tidak pernah dibuka,
     * supaya jadwal khusus yang tidak lengkap tidak membuka scan di jam yang salah.
     */
    private function dalamJendela(CarbonInterface $waktu, CarbonInterface $tanggal, string $buka, string $tutup): bool
    {
        $mulai = $this->awalMenit($tanggal, $buka);
        $akhir = $this->akhirMenit($tanggal, $tutup);

        return $mulai->lessThanOrEqualTo($akhir)
            && $waktu->greaterThanOrEqualTo($mulai)
            && $waktu->lessThanOrEqualTo($akhir);
    }

    private function awalMenit(CarbonInterface $tanggal, string $jam): CarbonInterface
    {
        return $tanggal->copy()->setTimeFromTimeString($jam)->startOfMinute();
    }

    private function akhirMenit(CarbonInterface $tanggal, string $jam): CarbonInterface
    {
        return $tanggal->copy()->setTimeFromTimeString($jam)->endOfMinute();
    }
}