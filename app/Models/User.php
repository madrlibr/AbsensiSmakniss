<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Enums\Role::class,
            'is_active' => 'boolean',
        ];
    }

    /* ── Relasi ─────────────────────────────────────────────── */

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class);
    }

    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class);
    }

    /** Kelas yang diampu sebagai wali kelas. */
    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_kelas_id');
    }

    /* ── Helper role ────────────────────────────────────────── */

    public function isAdmin(): bool
    {
        return $this->role === \App\Enums\Role::Admin;
    }

    public function isWaliKelas(): bool
    {
        return $this->role === \App\Enums\Role::WaliKelas;
    }

    /**
     * Role yang boleh mengakses modul scan gerbang/pulang.
     */
    public function isPetugasScan(): bool
    {
        return in_array($this->role, [\App\Enums\Role::Osis, \App\Enums\Role::Piket, \App\Enums\Role::Admin], true);
    }
}
