<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Guru = 'guru';
    case WaliKelas = 'wali_kelas';
    case Bk = 'bk';
    case OrangTua = 'orang_tua';
    case Osis = 'osis';
    case Piket = 'piket';

    /**
     * Label tampilan (Bahasa Indonesia) untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Guru => 'Guru Mata Pelajaran',
            self::WaliKelas => 'Wali Kelas',
            self::Bk => 'Guru BK',
            self::OrangTua => 'Orang Tua / Wali',
            self::Osis => 'Petugas OSIS',
            self::Piket => 'Guru Piket',
        };
    }

    /**
     * Nama pendek untuk chip di topbar.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Guru => 'Guru',
            self::WaliKelas => 'Wali Kelas',
            self::Bk => 'BK',
            self::OrangTua => 'Orang Tua',
            self::Osis => 'OSIS',
            self::Piket => 'Piket',
        };
    }

    /** Role dengan jalan pintas petugas: input manual & pulang cepat (Task 11/13). */
    public function bolehInputManual(): bool
    {
        return in_array($this, [self::Admin, self::Piket], true);
    }
}
