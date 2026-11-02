<?php

namespace App\Services\Absensi;

use App\Enums\StatusAbsensi;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Task 10 — Aturan jendela absen pulang (desain §7.4), berbasis pengaturan_hari:
 *  • Sebelum mulai_scan_pulang  → blokir "Belum waktunya pulang"
 *  • s/d akhir_scan_pulang      → pulang
 *  • Lewat akhir (scan QR)      → blokir "Jendela pulang berakhir — gunakan koreksi"
 * (Aksi piket `pulang_cepat` dengan karcis kapan pun = urusan endpoint Task 13.)
 */
class PulangRules
{
    public function __construct(private HariAktifRules $hariAktif) {}

    public function dalamJendela(CarbonInterface|string $waktu): HasilAturan
    {
        $waktu = Carbon::parse($waktu);
        $hari = $this->hariAktif->status($waktu);

        if ($pesan = $hari->pesanBlokir) {
            return HasilAturan::blokir($pesan);
        }

        $pengaturan = $hari->pengaturan;
        $jam = $waktu->format('H:i:s');
        $mulai = $pengaturan->mulai_scan_pulang->format('H:i:s');
        $akhir = $pengaturan->akhir_scan_pulang->format('H:i:s');

        if ($jam < $mulai) {
            return HasilAturan::blokir(sprintf(
                'Belum waktunya pulang — jendela %s–%s.',
                $pengaturan->mulai_scan_pulang->format('H:i'),
                $pengaturan->akhir_scan_pulang->format('H:i'),
            ));
        }

        if ($jam <= $akhir) {
            return HasilAturan::izinkan(StatusAbsensi::Pulang);
        }

        return HasilAturan::blokir('Jendela pulang berakhir — gunakan koreksi absensi.');
    }
}
