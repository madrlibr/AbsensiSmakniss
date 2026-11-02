<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Tujuh hari dalam seminggu — dipakai oleh jadwal, pengaturan_hari, dan job auto.
 * Nilai kolom disimpan huruf kecil (senin … minggu) agar ramah di MySQL & SQLite.
 */
enum Hari: string
{
    case Senin = 'senin';
    case Selasa = 'selasa';
    case Rabu = 'rabu';
    case Kamis = 'kamis';
    case Jumat = 'jumat';
    case Sabtu = 'sabtu';
    case Minggu = 'minggu';

    /** @return self[] */
    public static function semua(): array
    {
        return self::cases();
    }

    public static function labelDari(string $nilai): string
    {
        return self::tryFrom($nilai)?->label() ?? ucfirst($nilai);
    }

    public function label(): string
    {
        return match ($this) {
            self::Senin => 'Senin',
            self::Selasa => 'Selasa',
            self::Rabu => 'Rabu',
            self::Kamis => 'Kamis',
            self::Jumat => 'Jumat',
            self::Sabtu => 'Sabtu',
            self::Minggu => 'Minggu',
        };
    }

    /** Hari kerja standar sekolah (Senin–Jumat). */
    function hariKerja(): bool
    {
        return in_array($this, [self::Senin, self::Selasa, self::Rabu, self::Kamis, self::Jumat], true);
    }

    /** Enum hari dari sebuah tanggal (independen locale — index PHP date('w')). */
    public static function dariTanggal(CarbonInterface $tanggal): self
    {
        return self::from([
            'minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu',
        ][(int) $tanggal->format('w')]);
    }
}
