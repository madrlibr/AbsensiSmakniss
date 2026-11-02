<?php

namespace App\Models;

use App\Enums\StatusAbsensi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris pulang (Task 12): pre-created `belum_absen` tiap hari aktif,
 * di-update oleh scan gerbang pulang / piket / job auto-kabur (desain §6.16).
 */
class AbsensiPulang extends Model
{
    protected $table = 'absensi_pulang';

    protected $fillable = [
        'siswa_id', 'tanggal', 'waktu_scan', 'status', 'metode', 'scanned_by', 'kode_karcis', 'keterangan',
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
