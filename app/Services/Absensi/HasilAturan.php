<?php

namespace App\Services\Absensi;

use App\Enums\StatusAbsensi;

/**
 * Hasil aturan scan gerbang/pulang (Task 10).
 * izinkan=false → scan ditolak dengan pesanBlokir (tampil ke pengguna).
 */
final readonly class HasilAturan
{
    public function __construct(
        public bool $izinkan,
        public ?StatusAbsensi $status = null,
        public ?string $pesanBlokir = null,
        public bool $keteranganWajib = false,
    ) {}

    public static function izinkan(StatusAbsensi $status, bool $keteranganWajib = false): self
    {
        return new self(true, $status, null, $keteranganWajib);
    }

    public static function blokir(string $pesan): self
    {
        return new self(false, null, $pesan);
    }
}
