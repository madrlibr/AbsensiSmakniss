<?php

namespace App\Models;

use App\Enums\Hari;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan per hari (Senin–Minggu). 7 baris, semua jam dikustom via UI.
 * Jam NULL = belum dikonfigurasi → scan diblokir oleh service absensi.
 */
class PengaturanHari extends Model
{
    protected $table = 'pengaturan_hari';

    protected $fillable = [
        'hari', 'status_hari', 'buka_scan_gerbang', 'batas_tepat_waktu',
        'toleransi_menit', 'jam_tutup_gerbang', 'jam_auto_alpa',
        'mulai_scan_pulang', 'akhir_scan_pulang', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'hari' => Hari::class,
            'toleransi_menit' => 'integer',
            'buka_scan_gerbang' => 'datetime:H:i',
            'batas_tepat_waktu' => 'datetime:H:i',
            'jam_tutup_gerbang' => 'datetime:H:i',
            'jam_auto_alpa' => 'datetime:H:i',
            'mulai_scan_pulang' => 'datetime:H:i',
            'akhir_scan_pulang' => 'datetime:H:i',
        ];
    }

    /* ── Relasi & pencarian ────────────────────────────────── */

    /** Baris pengaturan untuk hari tertentu (menerima enum Hari atau string 'senin'). */
    public static function untuk(Hari|string $hari): ?self
    {
        $nilai = $hari instanceof Hari ? $hari->value : $hari;

        return static::where('hari', $nilai)->first();
    }

    /* ── Kondisi hari ──────────────────────────────────────── */

    public function libur(): bool
    {
        return $this->status_hari === 'libur';
    }

    /** Apakah seluruh jam kunci sudah terisi (scan diizinkan)? */
    public function terkonfigurasi(): bool
    {
        return $this->buka_scan_gerbang !== null
            && $this->batas_tepat_waktu !== null
            && $this->jam_tutup_gerbang !== null
            && $this->jam_auto_alpa !== null
            && $this->mulai_scan_pulang !== null
            && $this->akhir_scan_pulang !== null;
    }

    /** Hari masuk + jam lengkap → absensi boleh berjalan. */
    public function siapAbsensi(): bool
    {
        return ! $this->libur() && $this->terkonfigurasi();
    }

    /* ── Default bawaan (Task 9) ──────────────────────────── */

    /**
     * Nilai default per hari — dipakai seeder & tombol "Reset Default".
     * Senin–Jumat masuk 06:45–15:30; Sabtu & Minggu libur (jam NULL).
     *
     * @return array<string, mixed>
     */
    public static function defaultUntuk(Hari $hari): array
    {
        if ($hari->hariKerja()) {
            return [
                'status_hari' => 'masuk',
                'buka_scan_gerbang' => '06:45',
                'batas_tepat_waktu' => '07:00',
                'toleransi_menit' => 15,
                'jam_tutup_gerbang' => '07:30',
                'jam_auto_alpa' => '08:00',
                'mulai_scan_pulang' => '14:00',
                'akhir_scan_pulang' => '15:30',
                'keterangan' => null,
            ];
        }

        return [
            'status_hari' => 'libur',
            'buka_scan_gerbang' => null,
            'batas_tepat_waktu' => null,
            'toleransi_menit' => 15,
            'jam_tutup_gerbang' => null,
            'jam_auto_alpa' => null,
            'mulai_scan_pulang' => null,
            'akhir_scan_pulang' => null,
            'keterangan' => null,
        ];
    }

    /** Tulis ulang semua baris ke nilai default (dipakai tombol Reset Default). */
    public static function kembalikanDefault(): void
    {
        foreach (Hari::semua() as $hari) {
            static::updateOrCreate(['hari' => $hari->value], static::defaultUntuk($hari));
        }
    }
}
