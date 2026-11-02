<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    use SoftDeletes;

    protected $table = 'siswa';

    protected $fillable = [
        'nisn', 'nis', 'qr_code', 'nama', 'jenis_kelamin', 'foto', 'active',
    ];

    protected function casts(): array
    {
        return [
            'jenis_kelamin' => 'string',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Generate payload QR kartu pelajar otomatis setelah siswa dibuat:
        // format SISWA-{id}-{nisn}
        static::created(function (self $siswa): void {
            if (! $siswa->qr_code) {
                $siswa->updateQuietly(['qr_code' => sprintf('SISWA-%d-%s', $siswa->id, $siswa->nisn)]);
            }
        });
    }

    /* ── Relasi ─────────────────────────────────────────────── */

    /** Semua riwayat kelas lintas tahun ajaran. */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(SiswaKelas::class);
    }

    /** Keanggotaan kelas pada tahun ajaran tertentu. */
    public function kelasDiTa(int $tahunAjaranId): ?Kelas
    {
        return $this->riwayatKelas()
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('is_active', true)
            ->with('kelas')
            ->first()?->kelas;
    }

    /**
     * Kelas aktif tahun ajaran berjalan (akses sebagai atribut: $siswa->kelas_aktif).
     * Keanggotaan lewat pivot siswa_kelas, bukan kolom langsung.
     */
    public function getKelasAktifAttribute(): ?Kelas
    {
        $ta = TahunAjaran::aktif();

        return $ta ? $this->kelasDiTa($ta->id) : null;
    }

    public function orangTua(): BelongsToMany
    {
        return $this->belongsToMany(OrangTua::class, 'siswa_orang_tua')
            ->withPivot(['hubungan', 'is_primary'])
            ->using(SiswaOrangTua::class)
            ->withTimestamps();
    }

    public function pengajuanIzin(): HasMany
    {
        return $this->hasMany(PengajuanIzinSakit::class);
    }

    public function absensiGerbang(): HasMany
    {
        return $this->hasMany(AbsensiGerbang::class);
    }

    public function absensiMapel(): HasMany
    {
        return $this->hasMany(AbsensiMapel::class);
    }

    public function absensiPulang(): HasMany
    {
        return $this->hasMany(AbsensiPulang::class);
    }

    /* ── Scope & Helper ─────────────────────────────────────── */

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Siswa aktif yang terdaftar (pivot is_active) pada tahun ajaran aktif —
     * populasi target job otomatis (pre-create pulang & auto-alpa, Task 12).
     */
    public function scopeDiTaAktif(Builder $query): Builder
    {
        $ta = TahunAjaran::aktif();

        if (! $ta) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->aktif()
            ->whereHas('riwayatKelas', fn (Builder $k) => $k
                ->where('is_active', true)
                ->where('tahun_ajaran_id', $ta->id));
    }

    /** Lookup siswa lewat payload QR / NISN / NIS (untuk endpoint scan). */
    public static function cariIdentitas(string $payload): ?self
    {
        return static::query()
            ->where(fn (Builder $q) => $q
                ->where('qr_code', $payload)
                ->orWhere('nisn', $payload)
                ->orWhere('nis', $payload))
            ->first();
    }

    /** Inisial untuk avatar (mis. "Muhammad Rizky Pratama" → "MR"). */
    public function getInisialAttribute(): string
    {
        $parts = array_filter(explode(' ', trim($this->nama)));

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }
}
