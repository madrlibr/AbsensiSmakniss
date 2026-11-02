<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot siswa ↔ orang_tua (dipakai lewat relasi ->using() agar cast jalan).
 */
class SiswaOrangTua extends Pivot
{
    protected $table = 'siswa_orang_tua';

    public $incrementing = true;

    protected $fillable = ['siswa_id', 'orang_tua_id', 'hubungan', 'is_primary'];

    protected $casts = [
        'is_primary' => 'boolean',
    ];
}
