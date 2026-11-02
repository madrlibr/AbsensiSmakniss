<?php

namespace App\Models;

use App\Enums\StatusAbsensi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris kehadiran gerbang masuk (Task 11).
 * Satu baris per siswa per tanggal (index unik) — idempoten by design.
 */
class AbsensiGerbang extends Model
{
    protected $table = 'absensi_gerbang';

    protected $fillable = [
        'siswa_id', 'tanggal', 'waktu_scan', 'status', 'metode', 'scanned_by', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_scan' => 'datetime',
            'status' => StatusAbsensi::class,
        ];
    }

    /* ── Relasi ─────────────────────────────────────────────── */

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
