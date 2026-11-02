<?php

namespace App\Services\Absensi;

use App\Models\AbsensiPulang;
use App\Models\Siswa;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;

/**
 * Task 12 — Pre-create baris `absensi_pulang` = `belum_absen` untuk seluruh
 * siswa aktif di TA aktif (desain §7.5, dijalankan saat gerbang dibuka).
 * Idempoten: index unik (siswa_id, tanggal) + cek baris ada.
 */
class PrecreatePulang
{
    /** @return int jumlah baris yang benar-benar dibuat */
    public function jalankan(CarbonInterface $tanggal): int
    {
        $sudahAda = AbsensiPulang::whereDate('tanggal', $tanggal)->pluck('siswa_id')->all();

        $dibuat = 0;

        Siswa::diTaAktif()
            ->whereNotIn('id', $sudahAda)
            ->pluck('id')
            ->each(function (int $siswaId) use ($tanggal, &$dibuat) {
                try {
                    AbsensiPulang::create([
                        'siswa_id' => $siswaId,
                        'tanggal' => $tanggal,
                        'status' => 'belum_absen',
                        'metode' => 'auto',
                    ]);
                    $dibuat++;
                } catch (QueryException) {
                    // Baris dibuat proses lain di detik yang sama — biarkan
                }
            });

        return $dibuat;
    }
}
