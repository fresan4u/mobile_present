<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menguji endpoint scan murid. Senin 12 Oktober 2026 adalah hari kerja.
 * Titik tengah sekolah palsu: (-6.2, 106.8). Selisih 0,0002 derajat lintang = sekitar 22 meter.
 */
class ScanTest extends TestCase
{
    use RefreshDatabase;

    private User $murid;

    private string $rootDiskAsli;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'absensi.lokasi.aktif' => true,
            'absensi.lokasi.latitude' => -6.2,
            'absensi.lokasi.longitude' => 106.8,
            'absensi.lokasi.radius_meter' => 30,
            'absensi.lokasi.akurasi_maks_meter' => 30,
        ]);

        // Simpan lokasi disk asli sebelum diganti disk palsu, untuk tes keprivatan.
        $this->rootDiskAsli = (string) config('filesystems.disks.local.root');
        Storage::fake('local');

        $this->murid = User::factory()->create();
    }

    private function pada(string $waktu): void
    {
        $this->travelTo(Carbon::parse($waktu, 'Asia/Jakarta'));
    }

    private function foto(): UploadedFile
    {
        return UploadedFile::fake()->create('wajah.jpg', 100, 'image/jpeg');
    }

    /** Kirim scan sebagai murid yang sudah login. Bawaan: fingerprint, 22 meter dari sekolah. */
    private function kirim(array $isi = []): TestResponse
    {
        Sanctum::actingAs($this->murid);

        return $this->post('/api/murid/scan', array_merge([
            'metode' => 'fingerprint',
            'latitude' => -6.2002,
            'longitude' => 106.8,
            'akurasi' => 10,
        ], $isi), ['Accept' => 'application/json']);
    }

    private function absensiMurid(): ?Absensi
    {
        return Absensi::where('user_id', $this->murid->id)->first();
    }

    public function test_tanpa_login_ditolak(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->post('/api/murid/scan', [], ['Accept' => 'application/json'])->assertStatus(401);
    }

    public function test_scan_masuk_wajah_tepat_waktu_tercatat(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $respons = $this->kirim(['metode' => 'face_recognition', 'foto' => $this->foto()]);

        $respons->assertStatus(201)
            ->assertJsonPath('sesi', 'masuk')
            ->assertJsonPath('status', 'hadir')
            ->assertJsonPath('jam', '06:10:00')
            ->assertJsonPath('jarak_m', 22);

        $absensi = $this->absensiMurid();
        $this->assertSame('06:10:00', $absensi->jam_masuk);
        $this->assertSame('hadir', $absensi->status);
        $this->assertSame('face_recognition', $absensi->metode_masuk);
        $this->assertSame(22, $absensi->jarak_masuk_m);
        $this->assertSame(10, $absensi->akurasi_masuk_m);

        // Foto tersimpan di disk privat, dan lokasi filenya tidak pernah dikirim balik ke aplikasi.
        $this->assertNotNull($absensi->foto_masuk);
        $this->assertTrue(Storage::disk('local')->exists($absensi->foto_masuk));
        $this->assertStringNotContainsString($absensi->foto_masuk, $respons->getContent());
    }

    public function test_scan_masuk_terlambat_tercatat(): void
    {
        $this->pada('2026-10-12 06:30:00');

        $this->kirim()->assertStatus(201)->assertJsonPath('status', 'terlambat');

        $this->assertSame('terlambat', $this->absensiMurid()->status);
    }

    public function test_fingerprint_tanpa_foto_tercatat(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->kirim()->assertStatus(201);

        $absensi = $this->absensiMurid();
        $this->assertSame('fingerprint', $absensi->metode_masuk);
        $this->assertNull($absensi->foto_masuk);
    }

    public function test_wajah_tanpa_foto_ditolak(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->kirim(['metode' => 'face_recognition'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('foto');

        $this->assertSame(0, Absensi::count());
    }

    public function test_jam_dan_status_dari_aplikasi_diabaikan(): void
    {
        $this->pada('2026-10-12 06:30:00');

        $this->kirim(['jam_masuk' => '01:00:00', 'status' => 'hadir', 'user_id' => 999])
            ->assertStatus(201)
            ->assertJsonPath('status', 'terlambat')
            ->assertJsonPath('jam', '06:30:00');

        $absensi = $this->absensiMurid();
        $this->assertSame('06:30:00', $absensi->jam_masuk);
        $this->assertSame('terlambat', $absensi->status);
        $this->assertSame($this->murid->id, $absensi->user_id);
    }

    public function test_di_luar_radius_ditolak_tanpa_menyimpan(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->kirim(['metode' => 'face_recognition', 'foto' => $this->foto(), 'latitude' => -6.2004])
            ->assertStatus(422)
            ->assertJsonPath('alasan', 'di_luar_radius')
            ->assertJsonPath('jarak_m', 44);

        $this->assertSame(0, Absensi::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_akurasi_buruk_ditolak(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->kirim(['akurasi' => 50])
            ->assertStatus(422)
            ->assertJsonPath('alasan', 'akurasi_buruk');

        $this->assertSame(0, Absensi::count());
    }

    public function test_scan_masuk_ganda_ditolak(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->kirim()->assertStatus(201);
        $this->kirim()->assertStatus(422)->assertJsonPath('alasan', 'sudah_masuk');

        $this->assertSame(1, Absensi::count());
    }

    public function test_scan_pulang_setelah_masuk_tercatat(): void
    {
        $this->pada('2026-10-12 06:10:00');
        $this->kirim()->assertStatus(201);

        $this->pada('2026-10-12 15:10:00');
        $this->kirim()->assertStatus(201)->assertJsonPath('sesi', 'pulang');

        $absensi = $this->absensiMurid();
        $this->assertSame('06:10:00', $absensi->jam_masuk);
        $this->assertSame('15:10:00', $absensi->jam_pulang);
        $this->assertSame('hadir', $absensi->status);
        $this->assertSame('fingerprint', $absensi->metode_pulang);
        $this->assertSame(1, Absensi::count());
    }

    public function test_pulang_tanpa_masuk_ditolak(): void
    {
        $this->pada('2026-10-12 15:10:00');

        $this->kirim()->assertStatus(422)->assertJsonPath('alasan', 'belum_masuk');

        $this->assertSame(0, Absensi::count());
    }

    public function test_pulang_setelah_petugas_mengisi_terlambat_tercatat(): void
    {
        Absensi::forceCreate(['user_id' => $this->murid->id, 'tanggal' => '2026-10-12', 'status' => 'terlambat']);
        $this->pada('2026-10-12 15:10:00');

        $this->kirim()->assertStatus(201)->assertJsonPath('sesi', 'pulang');

        $absensi = $this->absensiMurid();
        $this->assertSame('terlambat', $absensi->status);
        $this->assertNull($absensi->jam_masuk);
        $this->assertSame('15:10:00', $absensi->jam_pulang);
        $this->assertSame(1, Absensi::count());
    }

    public function test_di_luar_jam_ditolak(): void
    {
        $this->pada('2026-10-12 10:00:00');

        $this->kirim()->assertStatus(422)->assertJsonPath('alasan', 'di_luar_jam');

        $this->assertSame(0, Absensi::count());
    }

    public function test_sakit_ditolak(): void
    {
        Absensi::forceCreate(['user_id' => $this->murid->id, 'tanggal' => '2026-10-12', 'status' => 'sakit']);
        $this->pada('2026-10-12 06:10:00');

        $this->kirim()->assertStatus(422)->assertJsonPath('alasan', 'diatur_petugas');
    }

    public function test_metode_tidak_valid_ditolak(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->kirim(['metode' => 'sidik'])->assertStatus(422)->assertJsonValidationErrors('metode');
    }

    public function test_koordinat_tidak_valid_ditolak(): void
    {
        $this->pada('2026-10-12 06:10:00');

        $this->kirim(['latitude' => 91])->assertStatus(422)->assertJsonValidationErrors('latitude');
    }

    public function test_foto_disimpan_di_disk_privat(): void
    {
        // Disk "local" harus mengarah ke folder privat (storage/app/private), bukan folder
        // yang dihubungkan ke alamat web.
        $root = str_replace('\\', '/', $this->rootDiskAsli);

        $this->assertStringEndsWith('/private', $root);
    }
}