<?php

namespace App\Enums;

/**
 * Status hari terhadap mesin absensi — hasil precedence §7.1:
 * libur rentang tidak_masuk > kegiatan (paksa aktif) > libur rutin > aktif.
 */
enum StatusHari: string
{
    case Aktif = 'aktif';
    case PaksaAktif = 'paksa_aktif';
    case LiburRutin = 'libur_rutin';
    case LiburMutlak = 'libur_mutlak';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Hari Aktif',
            self::PaksaAktif => 'Hari Aktif (Kegiatan)',
            self::LiburRutin => 'Libur Rutin',
            self::LiburMutlak => 'Libur Mutlak',
        };
    }

    /** Hari di mana scan & job otomatis diperbolehkan (semua selain libur). */
    public function bolehAbsensi(): bool
    {
        return in_array($this, [self::Aktif, self::PaksaAktif], true);
    }
}
