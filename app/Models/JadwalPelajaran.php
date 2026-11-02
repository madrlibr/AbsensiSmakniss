<?php

namespace App\Models;

use App\Enums\Hari;
use App\Enums\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Jadwal pelajaran: satu pertemuan tetap (kelas × hari × jam ke).
 * Keunikan & anti-bentrok guru/ruangan divalidasi di aplikasi (ValidasiBentrok).
 */
class JadwalPelajaran extends Model
{
    use SoftDeletes;

    protected $table = 'jadwal_pelajaran';

    protected $fillable = [
        'tahun_ajaran_id', 'kelas_id', 'mapel_id', 'guru_id',
        'hari', 'jam_ke', 'jam_mulai', 'jam_selesai',
        'ruangan', 'semester', 'active',
    ];

    protected function casts(): array
    {
        return [
            'hari' => Hari::class,
            'semester' => Semester::class,
            'jam_ke' => 'integer',
            'jam_mulai' => 'datetime:H:i',
            'jam_selesai' => 'datetime:H:i',
            'active' => 'boolean',
        ];
    }

    /* ── Relasi ────────────────────────────────────────────── */

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function absensiMapel(): HasMany
    {
        return $this->hasMany(AbsensiMapel::class);
    }
}
