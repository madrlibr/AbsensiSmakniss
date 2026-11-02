<?php

namespace App\Services\Absensi;

use App\Models\AbsensiGerbang;
use App\Models\Siswa;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;

/**
 * Task 12 — Auto-Alpa pada `jam_auto_alpa` (desain §7.5): insert `absensi_gerbang`
 * = `alpa` untuk siswa TANPA baris gerbang hari ini (anti-join). Baris `izin`/`sakit`
 * yang sudah ada membuat siswa otomatis terlewat. Idempoten: unique siswa+tanggal.
 */
class AutoAlpa
{
    /** @return int jumlah siswa yang dijadikan alpa */
    public function jalankan(CarbonInterface $tanggal): int
    {
        $dibuat = 0;

        Siswa::diTaAktif()
            ->whereDoesntHave('absensiGerbang', fn ($q) => $q->whereDate('tanggal', $tanggal))
            ->pluck('id')
            ->each(function (int $siswaId) use ($tanggal, &$dibuat) {
                try {
                    AbsensiGerbang::create([
                        'siswa_id' => $siswaId,
                        'tanggal' => $tanggal,
                        'waktu_scan' => null, // baris auto — tanpa waktu scan
                        'status' => 'alpa',
                        'metode' => 'auto',
                    ]);
                    $dibuat++;
                } catch (QueryException) {
                    // Scan siswa masuk di detik yang sama menang — bukan alpa
                }
            });

        return $dibuat;
    }
}
