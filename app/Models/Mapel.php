<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mapel extends Model
{
    use SoftDeletes;

    protected $table = 'mapel';

    protected $fillable = ['kode_mapel', 'nama_mapel', 'jumlah_jam_per_minggu'];

    protected function casts(): array
    {
        return [
            'jumlah_jam_per_minggu' => 'integer',
        ];
    }

    public function jadwalPelajaran(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }

    /** Guru yang mapel ini menjadi mapel utamanya. */
    public function guruUtama(): HasMany
    {
        return $this->hasMany(Guru::class, 'mapel_utama_id');
    }
}
