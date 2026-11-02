<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahunAjaran extends Model
{
    use SoftDeletes;

    protected $table = 'tahun_ajaran';

    protected $fillable = ['nama', 'tanggal_mulai', 'tanggal_selesai', 'is_active'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /* ── Relasi ─────────────────────────────────────────────── */

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    public function jadwalPelajaran(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }

    public function siswaKelas(): HasMany
    {
        return $this->hasMany(SiswaKelas::class);
    }

    public function hariLibur(): HasMany
    {
        return $this->hasMany(HariLibur::class);
    }

    /* ── Scope & Helper ─────────────────────────────────────── */

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Tahun ajaran yang sedang aktif (atau null). */
    public static function aktif(): ?self
    {
        return static::query()->aktif()->first();
    }
}
