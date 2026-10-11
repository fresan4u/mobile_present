<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// `user_id` sengaja TIDAK dimasukkan ke Fillable: pemilik baris harus ditentukan
// server dari akun yang sedang login, bukan dari input pengguna.
#[Fillable([
    'tanggal',
    'jam_masuk',
    'jam_pulang',
    'status',
    'metode_masuk',
    'metode_pulang',
    'foto_masuk',
    'foto_pulang',
    'latitude_masuk',
    'longitude_masuk',
    'jarak_masuk_m',
    'akurasi_masuk_m',
    'latitude_pulang',
    'longitude_pulang',
    'jarak_pulang_m',
    'akurasi_pulang_m',
    'catatan',
    'diubah_oleh',
])]
class Absensi extends Model
{
    /** Status yang ditentukan sistem saat murid scan masuk. */
    public const STATUS_HADIR = 'hadir';
    public const STATUS_TERLAMBAT = 'terlambat';

    /** Status yang diinput manual oleh petugas. */
    public const STATUS_SAKIT = 'sakit';
    public const STATUS_IZIN = 'izin';
    public const STATUS_TIDAK_HADIR = 'tidak_hadir';

    /** Cara murid melakukan scan. */
    public const METODE_FACE = 'face_recognition';
    public const METODE_FINGERPRINT = 'fingerprint';

    /**
     * Nama tabel ditulis eksplisit karena bukan bentuk jamak bahasa Inggris
     * ("absensi", bukan "absensis").
     */
    protected $table = 'absensi';

    /**
     * Kolom jam dibiarkan berupa teks "HH:MM:SS".
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'latitude_masuk' => 'decimal:7',
            'longitude_masuk' => 'decimal:7',
            'latitude_pulang' => 'decimal:7',
            'longitude_pulang' => 'decimal:7',
        ];
    }

    /** Murid pemilik baris absensi ini. */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Petugas yang terakhir mengoreksi baris ini (bila ada). */
    public function pengoreksi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}