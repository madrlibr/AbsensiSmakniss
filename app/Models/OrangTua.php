<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrangTua extends Model
{
    use SoftDeletes;

    protected $table = 'orang_tua';

    protected $fillable = ['user_id', 'nama', 'telepon', 'email'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'siswa_orang_tua')
            ->withPivot(['hubungan', 'is_primary'])
            ->using(SiswaOrangTua::class)
            ->withTimestamps();
    }

    public function pengajuanIzin(): HasMany
    {
        return $this->hasMany(PengajuanIzinSakit::class);
    }
}
