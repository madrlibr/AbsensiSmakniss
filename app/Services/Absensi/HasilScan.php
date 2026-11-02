<?php

namespace App\Services\Absensi;

use App\Models\AbsensiGerbang;
use App\Models\AbsensiPulang;
use App\Models\Siswa;

/**
 * Hasil proses scan gerbang (Task 11) & pulang (Task 13).
 * jenis: 'sukses' (baris dibuat) · 'info' (idempoten, sudah absen) · 'tolak'.
 */
final readonly class HasilScan
{
    public function __construct(
        public string $jenis,
        public string $pesan,
        public ?Siswa $siswa = null,
        public AbsensiGerbang|AbsensiPulang|null $baris = null,
    ) {}

    public static function sukses(string $pesan, Siswa $siswa, AbsensiGerbang|AbsensiPulang $baris): self
    {
        return new self('sukses', $pesan, $siswa, $baris);
    }

    public static function info(string $pesan, ?Siswa $siswa = null, AbsensiGerbang|AbsensiPulang|null $baris = null): self
    {
        return new self('info', $pesan, $siswa, $baris);
    }

    public static function tolak(string $pesan, ?Siswa $siswa = null): self
    {
        return new self('tolak', $pesan, $siswa);
    }
}
