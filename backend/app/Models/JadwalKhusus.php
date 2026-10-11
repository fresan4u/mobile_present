<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tanggal',
    'masuk_buka',
    'masuk_batas_hadir',
    'masuk_tutup',
    'pulang_buka',
    'pulang_tutup',
    'keterangan',
    'diubah_oleh',
])]
class JadwalKhusus extends Model
{
    /**
     * Nama tabel ditulis eksplisit karena bukan bentuk jamak bahasa Inggris
     * ("jadwal_khusus", bukan "jadwal_khususes").
     */
    protected $table = 'jadwal_khusus';

    /**
     * Kolom jam dibiarkan berupa teks "HH:MM:SS"; kolom jam yang kosong (null)
     * berarti memakai jam bawaan dari config/absensi.php.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}