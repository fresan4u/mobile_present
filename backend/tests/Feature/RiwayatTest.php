<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji endpoint riwayat absensi murid. "Hari ini" dipalsukan menjadi Senin 12 Oktober 2026,
 * sehingga jangkauan bawaan 30 hari adalah 13 September sampai 12 Oktober 2026.
 */
class RiwayatTest extends TestCase
{
    use RefreshDatabase;

    private User $murid;

    private User $lain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-12 12:00:00', 'Asia/Jakarta'));

        $this->murid = User::factory()->create();
        $this->lain = User::factory()->create();
    }

    private function isi(User $user, string $tanggal, string $status, array $lebih = []): void
    {
        Absensi::forceCreate(array_merge([
            'user_id' => $user->id,
            'tanggal' => $tanggal,
            'status' => $status,
        ], $lebih));
    }

    /** Enam catatan murid di dalam jangkauan, satu di luar jangkauan, dan dua milik murid lain. */
    private function isiData(): void
    {
        $this->isi($this->murid, '2026-10-12', 'hadir', ['jam_masuk' => '06:10:00', 'metode_masuk' => 'fingerprint']);
        $this->isi($this->murid, '2026-10-09', 'terlambat');
        $this->isi($this->murid, '2026-10-08', 'sakit');
        $this->isi($this->murid, '2026-10-07', 'izin');
        $this->isi($this->murid, '2026-10-06', 'tidak_hadir');
        $this->isi($this->murid, '2026-10-05', 'hadir');
        $this->isi($this->murid, '2026-09-01', 'hadir'); // di luar jangkauan 30 hari

        $this->isi($this->lain, '2026-10-12', 'hadir');
        $this->isi($this->lain, '2026-10-09', 'hadir');
    }

    private function ambil(string $query = ''): TestResponse
    {
        Sanctum::actingAs($this->murid);

        return $this->getJson('/api/murid/riwayat'.$query);
    }

    public function test_tanpa_login_ditolak(): void
    {
        $this->getJson('/api/murid/riwayat')->assertStatus(401);
    }

    public function test_bawaan_30_hari_terakhir(): void
    {
        $this->ambil()->assertOk()
            ->assertJsonPath('jangkauan.dari', '2026-09-13')
            ->assertJsonPath('jangkauan.sampai', '2026-10-12');
    }

    public function test_rekap_menghitung_semua_status_dalam_jangkauan(): void
    {
        $this->isiData();

        $this->ambil()->assertOk()
            ->assertJsonPath('rekap.hadir', 2)
            ->assertJsonPath('rekap.terlambat', 1)
            ->assertJsonPath('rekap.sakit', 1)
            ->assertJsonPath('rekap.izin', 1)
            ->assertJsonPath('rekap.tidak_hadir', 1);
    }

    public function test_daftar_terbaru_dulu_dan_hanya_data_sendiri(): void
    {
        $this->isiData();

        $this->ambil()->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.tanggal', '2026-10-12')
            ->assertJsonPath('data.0.jam_masuk', '06:10:00')
            ->assertJsonPath('data.0.metode_masuk', 'fingerprint')
            ->assertJsonPath('data.5.tanggal', '2026-10-05')
            ->assertJsonPath('halaman.total', 6);
    }

    public function test_jangkauan_kustom(): void
    {
        $this->isiData();

        $this->ambil('?dari=2026-10-08&sampai=2026-10-09')->assertOk()
            ->assertJsonPath('jangkauan.dari', '2026-10-08')
            ->assertJsonPath('jangkauan.sampai', '2026-10-09')
            ->assertJsonPath('halaman.total', 2)
            ->assertJsonPath('rekap.terlambat', 1)
            ->assertJsonPath('rekap.sakit', 1)
            ->assertJsonPath('rekap.hadir', 0);
    }

    public function test_pagination_dan_rekap_tetap_utuh(): void
    {
        $this->isiData();

        $this->ambil('?per_page=2&page=2')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.tanggal', '2026-10-08')
            ->assertJsonPath('halaman.saat_ini', 2)
            ->assertJsonPath('halaman.terakhir', 3)
            ->assertJsonPath('halaman.per_halaman', 2)
            ->assertJsonPath('halaman.total', 6)
            // Rekap dihitung dari seluruh jangkauan, bukan dari halaman ini saja.
            ->assertJsonPath('rekap.hadir', 2);
    }

    public function test_data_audit_tidak_bocor_tapi_catatan_petugas_tampil(): void
    {
        $this->isi($this->murid, '2026-10-12', 'hadir', [
            'jam_masuk' => '06:10:00',
            'foto_masuk' => 'absensi/2026-10-12/rahasia.jpg',
            'latitude_masuk' => -6.2002,
            'longitude_masuk' => 106.8,
            'jarak_masuk_m' => 22,
            'akurasi_masuk_m' => 10,
            'catatan' => 'Demam, surat keterangan menyusul',
        ]);

        $respons = $this->ambil()->assertOk()
            ->assertJsonPath('data.0.catatan', 'Demam, surat keterangan menyusul');

        $isi = $respons->getContent();
        $this->assertStringNotContainsString('rahasia.jpg', $isi);
        $this->assertStringNotContainsString('foto', $isi);
        $this->assertStringNotContainsString('latitude', $isi);
        $this->assertStringNotContainsString('longitude', $isi);
        $this->assertStringNotContainsString('jarak', $isi);
        $this->assertStringNotContainsString('akurasi', $isi);
    }

    public function test_per_page_terlalu_besar_ditolak(): void
    {
        $this->ambil('?per_page=101')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    public function test_format_tanggal_salah_ditolak(): void
    {
        $this->ambil('?dari=12-10-2026')->assertStatus(422)->assertJsonValidationErrors('dari');
    }

    public function test_tanggal_terbalik_ditolak(): void
    {
        $this->ambil('?dari=2026-10-10&sampai=2026-10-01')->assertStatus(422)->assertJsonValidationErrors('sampai');
    }

    public function test_jangkauan_terlalu_panjang_ditolak(): void
    {
        $this->ambil('?dari=2025-01-01&sampai=2026-10-12')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Jangkauan tanggal maksimal 366 hari.');
    }

    public function test_hanya_dari_setelah_hari_ini_ditolak(): void
    {
        $this->ambil('?dari=2026-10-20')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tanggal awal tidak boleh setelah tanggal akhir.');
    }
}