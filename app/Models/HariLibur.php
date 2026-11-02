<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Libur rentang/tanggal dalam satu tahun ajaran.
 * Libur rutin (per hari) diatur di pengaturan_hari.status_hari, bukan di sini.
 */
class HariLibur extends Model
{
    protected $table = 'hari_libur';

    protected $fillable = [
        'tahun_ajaran_id', 'tanggal_mulai', 'tanggal_selesai',
        'keterangan', 'status_masuk', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /* ── Relasi ────────────────────────────────────────────── */

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /* ── Scope & kondisi ───────────────────────────────────── */

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    /** Libur mutlak: aktif & status tidak_masuk. */
    public function liburMutlak(): bool
    {
        return $this->is_active && $this->status_masuk === 'tidak_masuk';
    }

    /** Apakah tanggal (Y-m-d) berada di rentang baris ini? */
    public function mencakup(string $tanggal): bool
    {
        $t = \Carbon\Carbon::parse($tanggal)->startOfDay();
        $mulai = $this->tanggal_mulai->copy()->startOfDay();
        $selesai = $this->tanggal_selesai?->copy()->startOfDay() ?? $mulai;

        return $t->betweenIncluded($mulai, $selesai);
    }
}
