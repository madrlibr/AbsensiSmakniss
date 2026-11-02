<?php

namespace App\Services\Absensi;

use App\Models\AbsensiGerbang;
use App\Models\AbsensiPulang;
use App\Enums\StatusAbsensi;
use Carbon\CarbonInterface;

/**
 * Task 12 — Auto-Kabur setelah `akhir_scan_pulang` (desain §7.5): update
 * `absensi_pulang` `belum_absen` → `kabur` HANYA untuk siswa yang hadir pagi
 * (gerbang `hadir_tepat_waktu`/`terlambat`). Siswa alpa/izin/sakit dibiarkan
 * `belum_absen` (rekap menampilkan `-`). Idempoten by status guard.
 */
class AutoKabur
{
    /** @return int jumlah baris yang berubah jadi kabur */
    public function jalankan(CarbonInterface $tanggal): int
    {
        $hadirPagi = AbsensiGerbang::whereDate('tanggal', $tanggal)
            ->whereIn('status', [StatusAbsensi::HadirTepatWaktu->value, StatusAbsensi::Terlambat->value])
            ->pluck('siswa_id');

        if ($hadirPagi->isEmpty()) {
            return 0;
        }

        return AbsensiPulang::whereDate('tanggal', $tanggal)
            ->where('status', StatusAbsensi::BelumAbsen->value)
            ->whereIn('siswa_id', $hadirPagi)
            ->update(['status' => StatusAbsensi::Kabur->value]);
    }
}
