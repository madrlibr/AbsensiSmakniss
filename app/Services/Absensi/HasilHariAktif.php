<?php

namespace App\Services\Absensi;

use App\Enums\StatusHari;
use App\Models\PengaturanHari;

/**
 * Hasil penentuan hari aktif/libur (Task 10).
 * pesanBlokir null = hari boleh dipakai absensi.
 */
final readonly class HasilHariAktif
{
    public function __construct(
        public StatusHari $status,
        public ?PengaturanHari $pengaturan = null,
        public ?string $pesanBlokir = null,
        public ?string $keteranganLibur = null,
    ) {}

    /** Boleh scan/absen pada hari ini (libur & jam belum dikonfigurasi = false). */
    public function bolehAbsensi(): bool
    {
        return $this->pesanBlokir === null;
    }

    /** Job otomatis (auto-alpa/kabur/pre-create) hanya jalan pada hari aktif. */
    public function bolehDikerjakanJob(): bool
    {
        return $this->status->bolehAbsensi();
    }
}
