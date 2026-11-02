<?php

namespace App\Services\Absensi;

use App\Enums\StatusAbsensi;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Task 10 — Aturan absen gerbang masuk (desain §7.2), berbasis pengaturan_hari:
 *  • Sebelum buka_scan_gerbang  → blokir "Gerbang belum dibuka"
 *  • s/d batas_tepat_waktu      → hadir_tepat_waktu
 *  • s/d jam_tutup_gerbang      → terlambat
 *  • Lewat tutup, scan QR       → blokir "Gerbang ditutup"
 *  • Lewat tutup, input petugas → terlambat + keterangan wajib
 */
class GerbangRules
{
    public function __construct(private HariAktifRules $hariAktif) {}

    /**
     * @param  bool  $inputPetugas  true bila input manual petugas piket/admin
     *                              (boleh melewati jam tutup dengan keterangan wajib).
     */
    public function tentukanStatus(CarbonInterface|string $waktu, bool $inputPetugas = false): HasilAturan
    {
        $waktu = Carbon::parse($waktu);
        $hari = $this->hariAktif->status($waktu);

        if ($pesan = $hari->pesanBlokir) {
            return HasilAturan::blokir($pesan);
        }

        $pengaturan = $hari->pengaturan;
        $jam = $waktu->format('H:i:s');

        if ($jam < $pengaturan->buka_scan_gerbang->format('H:i:s')) {
            return HasilAturan::blokir(
                'Gerbang belum dibuka — buka pukul '.$pengaturan->buka_scan_gerbang->format('H:i').'.',
            );
        }

        if ($jam <= $pengaturan->batas_tepat_waktu->format('H:i:s')) {
            return HasilAturan::izinkan(StatusAbsensi::HadirTepatWaktu);
        }

        if ($jam <= $pengaturan->jam_tutup_gerbang->format('H:i:s')) {
            return HasilAturan::izinkan(StatusAbsensi::Terlambat);
        }

        if ($inputPetugas) {
            return HasilAturan::izinkan(StatusAbsensi::Terlambat, keteranganWajib: true);
        }

        return HasilAturan::blokir('Gerbang ditutup — lapor petugas piket untuk input manual.');
    }
}
