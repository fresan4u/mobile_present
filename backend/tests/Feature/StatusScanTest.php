<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji aturan "apa yang boleh dilakukan murid sekarang" dengan jam palsu.
 * 12 Oktober 2026 adalah hari Senin, 10 Oktober 2026 adalah hari Sabtu.
 */
class StatusScanTest extends TestCase
{
    use RefreshDatabase;

    private User $murid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->murid = User::factory()->create();
    }

    /** Pindahkan jam server ke waktu tertentu (WIB). */
    private function pada(string $waktu): void
    {
        $this->travelTo(Carbon::parse($waktu, 'Asia/Jakarta'));
    }

    /** Buat baris absensi untuk murid contoh pada hari Senin 12 Oktober. */
    private function absensi(array $isi): void
    {
        Absensi::forceCreate(array_merge(['user_id' => $this->murid->id, 'tanggal' => '2026-10-12'], $isi));
    }

    /** Panggil endpoint status scan sebagai murid yang sudah login. */
    private function ambilStatus(): TestResponse
    {
        Sanctum::actingAs($this->murid);

        return $this->getJson('/api/murid/status-scan');
    }

    public function test_tanpa_login_ditolak(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->getJson('/api/murid/status-scan')->assertStatus(401);
    }

    public function test_sesi_masuk_tepat_waktu_bisa_scan(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('scan.sesi', 'masuk')
            ->assertJsonPath('scan.status_masuk', 'hadir')
            ->assertJsonPath('bisa_scan', true)
            ->assertJsonPath('alasan', null);
    }

    public function test_sesi_masuk_terlambat_bisa_scan(): void
    {
        $this->pada('2026-10-12 06:30:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('scan.status_masuk', 'terlambat')
            ->assertJsonPath('bisa_scan', true);
    }

    public function test_sudah_scan_masuk_tidak_bisa_scan_lagi(): void
    {
        $this->absensi(['jam_masuk' => '06:05:00', 'status' => 'hadir']);
        $this->pada('2026-10-12 06:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'sudah_masuk');
    }

    public function test_status_terlambat_dari_petugas_menutup_scan_masuk(): void
    {
        $this->absensi(['jam_masuk' => null, 'status' => 'terlambat']);
        $this->pada('2026-10-12 06:30:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'sudah_masuk');
    }

    public function test_sakit_tidak_bisa_scan_masuk(): void
    {
        $this->absensi(['status' => 'sakit']);
        $this->pada('2026-10-12 06:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'diatur_petugas');
    }

    public function test_pulang_tanpa_tercatat_masuk_ditolak(): void
    {
        $this->pada('2026-10-12 15:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('scan.sesi', 'pulang')
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'belum_masuk');
    }

    public function test_pulang_setelah_scan_masuk_bisa_scan(): void
    {
        $this->absensi(['jam_masuk' => '06:05:00', 'status' => 'hadir']);
        $this->pada('2026-10-12 15:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', true)
            ->assertJsonPath('alasan', null);
    }

    public function test_pulang_setelah_petugas_mengisi_terlambat_bisa_scan(): void
    {
        $this->absensi(['jam_masuk' => null, 'status' => 'terlambat']);
        $this->pada('2026-10-12 15:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', true);
    }

    public function test_pulang_saat_sakit_ditolak(): void
    {
        $this->absensi(['status' => 'sakit']);
        $this->pada('2026-10-12 15:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'diatur_petugas');
    }

    public function test_pulang_saat_tidak_hadir_ditolak(): void
    {
        $this->absensi(['status' => 'tidak_hadir']);
        $this->pada('2026-10-12 15:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'belum_masuk');
    }

    public function test_sudah_scan_pulang_tidak_bisa_scan_lagi(): void
    {
        $this->absensi(['jam_masuk' => '06:05:00', 'jam_pulang' => '15:05:00', 'status' => 'hadir']);
        $this->pada('2026-10-12 15:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'sudah_pulang');
    }

    public function test_di_luar_jam_ditutup(): void
    {
        $this->pada('2026-10-12 10:00:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('scan.sesi', 'tutup')
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'di_luar_jam');
    }

    public function test_akhir_pekan_ditutup(): void
    {
        $this->pada('2026-10-10 06:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'akhir_pekan');
    }

    public function test_hari_libur_ditutup(): void
    {
        HariLibur::create(['tanggal' => '2026-10-12', 'keterangan' => 'Libur contoh']);
        $this->pada('2026-10-12 06:10:00');

        $this->ambilStatus()->assertOk()
            ->assertJsonPath('bisa_scan', false)
            ->assertJsonPath('alasan', 'hari_libur')
            ->assertJsonPath('scan.keterangan', 'Libur contoh');
    }
}