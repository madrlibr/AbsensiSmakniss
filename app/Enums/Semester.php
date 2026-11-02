<?php

namespace App\Enums;

/**
 * Semester akademik pada satu tahun ajaran.
 */
enum Semester: string
{
    case Ganjil = 'ganjil';
    case Genap = 'genap';

    /** @return self[] */
    public static function semua(): array
    {
        return self::cases();
    }

    public function label(): string
    {
        return match ($this) {
            self::Ganjil => 'Ganjil',
            self::Genap => 'Genap',
        };
    }
}
