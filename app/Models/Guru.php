<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guru extends Model
{
    use SoftDeletes;

    protected $table = 'guru';

    protected $fillable = ['user_id', 'nip', 'telepon', 'mapel_utama_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mapelUtama(): BelongsTo
    {
        return $this->belongsTo(Mapel::class, 'mapel_utama_id');
    }

    public function jadwalPelajaran(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }

    /** Nama guru (dari tabel users). */
    public function getNamaAttribute(): string
    {
        return $this->user?->name ?? '-';
    }
}
