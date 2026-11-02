<?php

namespace App\Enums;

/**
 * Seluruh status absensi lintas tiga tabel (gerbang, mapel, pulang).
 * Dipakai juga oleh komponen x-ui.status-badge untuk warna & ikon.
 */
enum StatusAbsensi: string
{
    case HadirTepatWaktu = 'hadir_tepat_waktu';
    case Terlambat = 'terlambat';
    case Alpa = 'alpa';
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Pulang = 'pulang';
    case PulangCepat = 'pulang_cepat';
    case Kabur = 'kabur';
    case BelumAbsen = 'belum_absen';

    public function label(): string
    {
        return match ($this) {
            self::HadirTepatWaktu => 'Hadir Tepat Waktu',
            self::Terlambat => 'Terlambat',
            self::Alpa => 'Alpa',
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::Pulang => 'Pulang',
            self::PulangCepat => 'Pulang Cepat',
            self::Kabur => 'Kabur',
            self::BelumAbsen => 'Belum Absen',
        };
    }

    public function ikon(): string
    {
        return match ($this) {
            self::HadirTepatWaktu => 'verified',
            self::Terlambat => 'schedule',
            self::Alpa => 'cancel',
            self::Izin => 'description',
            self::Sakit => 'medical_services',
            self::Pulang => 'logout',
            self::PulangCepat => 'directions_run',
            self::Kabur => 'priority_high',
            self::BelumAbsen => 'radio_button_unchecked',
        };
    }

    /**
     * Kelas badge (skala warna DESIGN.md):
     * hijau = kehadiran/pulang baik, kuning = perhatian, merah = bermasalah,
     * biru = izin/sakit, netral = belum terjadi.
     */
    public function kelasBadge(): string
    {
        return match ($this) {
            self::HadirTepatWaktu, self::Pulang => 'bg-tertiary-fixed text-on-tertiary-fixed',
            self::Terlambat, self::PulangCepat => 'bg-[#fff3c4] text-[#7a4f00]',
            self::Alpa, self::Kabur => 'bg-error-container text-on-error-container',
            self::Izin, self::Sakit => 'bg-primary-fixed text-on-primary-fixed',
            self::BelumAbsen => 'bg-surface-container text-on-surface-variant',
        };
    }

    /** Apakah termasuk kategori izin/sakit (diblokir dari auto-alpa). */
    public function izinAtauSakit(): bool
    {
        return in_array($this, [self::Izin, self::Sakit], true);
    }
}
