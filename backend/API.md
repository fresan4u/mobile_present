# API Absensi Digital

Dokumen ini mendaftar endpoint yang sudah jadi dan teruji, serta aturan yang berlaku.
Perbarui setiap kali ada endpoint atau aturan baru.

## Menjalankan backend di laptop sendiri

1. PHP **8.3 atau lebih baru** (Laravel 13 tidak jalan di PHP 8.2).
2. Dari folder `backend`: `composer install`
3. Salin `.env.example` menjadi `.env`, lalu `php artisan key:generate`
4. Isi pengaturan database MySQL di `.env`, lalu `php artisan migrate`
5. Tambahkan pengaturan absensi ke `.env` (nilai bawaan ada di `config/absensi.php`):

```
ABSENSI_LOKASI_AKTIF=true
ABSENSI_LATITUDE=
ABSENSI_LONGITUDE=
ABSENSI_RADIUS_METER=30
ABSENSI_AKURASI_MAKS_METER=30
```

`ABSENSI_LATITUDE` dan `ABSENSI_LONGITUDE` adalah titik tengah sekolah. Selama keduanya kosong dan
`ABSENSI_LOKASI_AKTIF=true`, semua scan ditolak (alasan `lokasi_sekolah_belum_diatur`).

6. Jalankan tes: `php artisan test` (memakai SQLite di memori, tidak menyentuh MySQL).

## Ketentuan umum

- Awalan semua alamat: `/api`
- Setiap permintaan wajib memakai header `Accept: application/json`.
- Endpoint yang butuh login memakai header `Authorization: Bearer <token>` (token dari `/api/login`).
- Kesalahan isian: status `422` dengan bentuk `{ "message": "...", "errors": { "kolom": ["..."] } }`.
- Tanpa login atau token salah: `401` `{ "message": "Unauthenticated." }`.
- Terlalu banyak permintaan: `429`.
- Semua jam dan tanggal memakai WIB (`Asia/Jakarta`) dan **ditentukan server**, tidak pernah dari kiriman aplikasi.

## Login

### `POST /api/login`

Untuk semua peran (murid, petugas, admin). Dibatasi 5 percobaan per menit per kombinasi identitas dan IP.

Permintaan (JSON):

```json
{ "identitas": "TES001", "password": "rahasia123" }
```

`identitas` berisi NIS atau email (jika berbentuk email, dicocokkan ke kolom `email`, selain itu ke `nis`).

Berhasil `200`:

```json
{
  "token": "1|...",
  "user": { "id": 2, "name": "Tes Murid", "role": "siswa", "nis": "TES001", "nisn": null, "kelas": "X 10.1", "email": null, "foto": null }
}
```

Gagal `401`: `{ "message": "NIS/email atau password salah." }` (sama untuk akun tidak ada dan password salah).

### `GET /api/me` (login)

Mengembalikan `{ "user": { ... } }` dengan bentuk yang sama seperti di atas.

### `POST /api/logout` (login)

Mencabut token yang dipakai. Respons `{ "message": "Berhasil keluar." }`.

## Endpoint murid (awalan `/api/murid`, login)

### `GET /api/murid/status-scan`

Dipakai beranda dan halaman scan untuk tahu apa yang boleh dilakukan sekarang.

```json
{
  "scan": {
    "tanggal": "2026-10-12",
    "waktu_server": "2026-10-12T06:10:00+07:00",
    "sesi": "masuk",
    "status_masuk": "hadir",
    "alasan_tutup": null,
    "keterangan": null,
    "jadwal": {
      "masuk": { "buka": "05:30", "batas_hadir": "06:20", "tutup": "06:45" },
      "pulang": { "buka": "15:00", "tutup": "15:45" }
    }
  },
  "absensi_hari_ini": null,
  "bisa_scan": true,
  "alasan": null
}
```

- `scan.sesi`: `masuk`, `pulang`, atau `tutup`.
- `scan.status_masuk`: `hadir` atau `terlambat` (hanya terisi saat sesi `masuk`).
- `scan.alasan_tutup`: `hari_libur`, `akhir_pekan`, atau `di_luar_jam` (hanya terisi saat sesi `tutup`).
- `scan.keterangan`: nama hari libur atau keterangan jadwal khusus, bila ada.
- `absensi_hari_ini`: `null`, atau objek `{ tanggal, jam_masuk, jam_pulang, status, metode_masuk, metode_pulang, catatan }`.
- `bisa_scan` dan `alasan`: lihat tabel alasan di bawah.

### `POST /api/murid/scan`

Scan masuk atau pulang. Sesi (masuk atau pulang) dan status (hadir atau terlambat) ditentukan server dari jam.
Dibatasi 10 permintaan per menit per akun. Kirim sebagai `multipart/form-data`.

| Kolom | Wajib | Keterangan |
|---|---|---|
| `metode` | ya | `face_recognition` atau `fingerprint` |
| `latitude` | ya | -90 sampai 90 |
| `longitude` | ya | -180 sampai 180 |
| `akurasi` | ya | akurasi GPS dalam meter (dari `coords.accuracy`) |
| `foto` | ya bila `face_recognition` | JPEG atau PNG, maksimal 2 MB |

Berhasil `201`:

```json
{
  "message": "Absensi masuk berhasil dicatat.",
  "sesi": "masuk",
  "status": "hadir",
  "jam": "06:10:00",
  "jarak_m": 22,
  "akurasi_m": 10,
  "absensi_hari_ini": { "tanggal": "2026-10-12", "jam_masuk": "06:10:00", "jam_pulang": null, "status": "hadir", "metode_masuk": "face_recognition", "metode_pulang": null }
}
```

Ditolak `422` (bukan kesalahan isian):

```json
{ "message": "Kamu berada di luar area sekolah.", "alasan": "di_luar_radius", "jarak_m": 44, "akurasi_m": 10 }
```

`jarak_m` dan `akurasi_m` hanya ikut pada penolakan karena lokasi.

### `GET /api/murid/riwayat`

Riwayat absensi murid yang sedang login (hanya datanya sendiri), terbaru dulu.

| Query | Bawaan | Keterangan |
|---|---|---|
| `dari` | 29 hari sebelum `sampai` | format `2026-10-01` |
| `sampai` | hari ini (WIB) | format `2026-10-31` |
| `per_page` | 30 | 1 sampai 100 |
| `page` | 1 | nomor halaman |

Jangkauan maksimal 366 hari. Tanggal awal tidak boleh setelah tanggal akhir.

```json
{
  "jangkauan": { "dari": "2026-09-13", "sampai": "2026-10-12" },
  "rekap": { "hadir": 2, "terlambat": 1, "sakit": 1, "izin": 1, "tidak_hadir": 1 },
  "data": [
    { "tanggal": "2026-10-12", "jam_masuk": "06:10:00", "jam_pulang": null, "status": "hadir", "metode_masuk": "fingerprint", "metode_pulang": null, "catatan": null }
  ],
  "halaman": { "saat_ini": 1, "terakhir": 1, "per_halaman": 30, "total": 6 }
}
```

`rekap` dihitung dari seluruh jangkauan, bukan dari satu halaman. Hari tanpa baris absensi tidak muncul.
Foto, koordinat, jarak, dan akurasi sengaja tidak dikirim ke murid.

## Alasan scan tidak bisa dilakukan

| `alasan` | Arti |
|---|---|
| `sudah_masuk` | Sudah tercatat masuk (scan, atau petugas sudah mengisi Hadir/Terlambat) |
| `sudah_pulang` | Sudah tercatat pulang |
| `belum_masuk` | Belum tercatat masuk, jadi belum bisa scan pulang (temui petugas piket) |
| `diatur_petugas` | Status Sakit atau Izin diatur petugas |
| `hari_libur` | Tanggal ada di tabel `hari_libur` |
| `akhir_pekan` | Di luar `hari_aktif` (Senin sampai Jumat) |
| `di_luar_jam` | Di luar jendela scan masuk dan pulang |
| `di_luar_radius` | Jarak ke titik tengah sekolah melebihi radius |
| `akurasi_buruk` | Akurasi GPS lebih buruk dari batas |
| `lokasi_sekolah_belum_diatur` | Titik tengah sekolah belum diisi di `.env` |

## Aturan yang berlaku

- **Jadwal bawaan** (`config/absensi.php`): scan masuk 05:30 sampai 06:45 (Hadir sampai 06:20, Terlambat 06:21 sampai 06:45),
  scan pulang 15:00 sampai 15:45. Setiap batas berlaku sampai **akhir menit** (06:20 berarti masih berlaku sampai 06:20:59).
- **Urutan pengecekan**: hari libur, lalu akhir pekan, lalu jam. Jam dibaca dari `jadwal_khusus` bila tanggal itu punya baris,
  jika tidak dari config. Kolom jam yang kosong di `jadwal_khusus` memakai jam bawaan.
- **Jendela terbalik dianggap tidak pernah dibuka** (jam buka lebih besar dari jam tutup setelah digabung dengan jam bawaan).
  Contoh: petugas hanya mengisi `pulang_tutup = 12:00` tanpa `pulang_buka`, maka scan pulang tutup seharian.
  Karena itu jadwal khusus yang mengubah jam pulang harus mengisi **jam buka dan jam tutup**.
- **Lokasi** (dicek di server): diterima bila jarak ke titik tengah sekolah kurang dari atau sama dengan 30 meter
  **dan** akurasi GPS kurang dari atau sama dengan 30 meter. Keduanya dapat diubah lewat `.env`.
- **Tercatat hadir** berarti punya `jam_masuk` **atau** status `hadir`/`terlambat`. Murid yang tercatat hadir boleh scan pulang.
  Status `sakit`/`izin` menutup scan masuk dan pulang. Status `tidak_hadir` tidak membuka scan pulang.
- **Satu baris per murid per tanggal** (kunci unik `user_id` + `tanggal`). Murid yang tidak scan tidak punya baris,
  kecuali petugas menginputnya.
- **Foto bukti** (hanya scan wajah) disimpan di disk `local`, yaitu `storage/app/private`, **tanpa alamat web**.
  Jangan pernah memindahkannya ke disk `public`, dan jangan mengirim lokasi file ke aplikasi murid.
  Foto hanya boleh dibuka lewat endpoint petugas atau admin yang memeriksa peran.

## Tabel

- `users`: `role` (`siswa`, `petugas`, `admin`; bawaan `siswa`), `nis`, `nisn`, `kelas` (contoh `X 10.1`), `foto`, `email` (boleh kosong).
  Kolom `role` **tidak** ada di `#[Fillable]`; isi eksplisit, misalnya `$user->role = 'petugas'`.
- `absensi`: `user_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `status` (`hadir`, `terlambat`, `sakit`, `izin`, `tidak_hadir`; boleh kosong),
  `metode_masuk`/`metode_pulang`, `foto_masuk`/`foto_pulang`, koordinat, jarak, dan akurasi untuk masuk dan pulang,
  `catatan`, `diubah_oleh` (petugas yang terakhir mengoreksi).
- `hari_libur`: `tanggal` (unik), `keterangan`, `dibuat_oleh`.
- `jadwal_khusus`: `tanggal` (unik), `masuk_buka`, `masuk_batas_hadir`, `masuk_tutup`, `pulang_buka`, `pulang_tutup`,
  `keterangan`, `diubah_oleh`. Berlaku untuk satu tanggal saja.

Kode penentu jadwal: `app/Services/JadwalAbsensi.php`. Aturan boleh scan: `app/Services/AturanScan.php`. Lokasi: `app/Services/ValidasiLokasi.php`.

## Yang perlu dibangun untuk sisi petugas

Disarankan di bawah awalan `/api/petugas`:

- **Daftar Data Absensi per tanggal** dengan filter (nama/kelas, tanggal, kelas, status, metode), pagination, dan ekspor.
  Murid yang tidak punya baris harus tetap muncul (gabungkan semua murid dengan absensi tanggal itu, jam kosong).
- **Edit status** (Hadir, Terlambat, Sakit, Izin, Tidak Hadir) dan catatan. Bila murid belum punya baris untuk tanggal itu,
  **buat baris baru**, dan isi `diubah_oleh` dengan petugas yang bersangkutan. Desain saat ini baru menawarkan Sakit, Izin, dan
  Tidak Hadir; **tambahkan Terlambat dan Hadir**, karena itu dipakai untuk murid yang diurus petugas piket.
  Mengisi Terlambat dengan jam masuk kosong membuat murid boleh scan pulang.
- **Menampilkan foto bukti** lewat endpoint yang memeriksa peran, lalu membaca dari disk `local`.
- **Kelola `hari_libur` dan `jadwal_khusus`.** Validasi jam: setelah digabung dengan jam bawaan, jam buka tidak boleh setelah jam tutup.
- **Membuat akun murid dan petugas** (isi `role` secara eksplisit).

## Yang belum ada (jujur)

- **Rute `/api/murid/*` belum memeriksa peran.** Akun petugas atau admin yang login juga bisa memanggilnya.
  Perlu middleware peran (misalnya `role:siswa` untuk murid, `role:petugas,admin` untuk petugas). Belum dibuat.
- **Pencocokan wajah di server belum ada.** `metode=face_recognition` hanya klaim dari aplikasi; foto disimpan sebagai bukti audit.
- **Fingerprint (WebAuthn) belum diverifikasi di server.** `metode=fingerprint` juga hanya klaim.
- **Geolokasi dapat dipalsukan** dengan aplikasi lokasi palsu. Koordinat, jarak, dan akurasi disimpan di tiap baris untuk diaudit.
- **Token Sanctum belum punya masa berlaku** (bawaan: tidak kedaluwarsa).
- **CORS belum diatur** untuk alamat web murid dan web petugas.
- **Zona waktu aplikasi** di `config/app.php` kemungkinan masih UTC (bawaan Laravel), sehingga `created_at` dan `updated_at` bukan WIB.
  Gunakan `tanggal`, `jam_masuk`, dan `jam_pulang` untuk tampilan.
- **Kebijakan penyimpanan foto** (berapa lama disimpan, penghapusan otomatis) belum diputuskan.