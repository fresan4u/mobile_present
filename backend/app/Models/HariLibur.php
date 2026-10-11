<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tanggal', 'keterangan', 'dibuat_oleh'])]
class HariLibur extends Model
{
    /**
     * Nama tabel ditulis eksplisit karena bukan bentuk jamak bahasa Inggris
     * ("hari_libur", bukan "hari_liburs").
     */
    protected $table = 'hari_libur';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}