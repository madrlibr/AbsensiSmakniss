<?php

namespace App\Services\Absensi;

use App\Enums\Hari;
use App\Enums\StatusHari;
use App\Models\HariLibur;
use App\Models\PengaturanHari;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Task 10 — Penentuan hari aktif/libur, precedence desain §7.1:
 *  1. Rentang hari_libur status_masuk = tidak_masuk → LIBUR MUTLAK.
 *  2. Rentang hari_libur status_masuk = masuk (kegiatan) → HARI AKTIF PAKSA,
 *     tetap memakai pengaturan_hari hari itu (jam NULL → diblokir).
 *  3. pengaturan_hari.status_hari = libur → LIBUR RUTIN.
 *  4. Selain itu → HARI AKTIF dengan aturan waktu pengaturan_hari hari itu.
 */
class HariAktifRules
{
    public function status(CarbonInterface|string $tanggal): HasilHariAktif
    {
        $tanggal = Carbon::parse($tanggal)->startOfDay();
        $hari = Hari::dariTanggal($tanggal);
        $pengaturan = PengaturanHari::untuk($hari);

        // 1) Libur mutlak — prioritas tertinggi
        $liburMutlak = $this->cariLibur($tanggal, 'tidak_masuk');
        if ($liburMutlak) {
            return new HasilHariAktif(
                StatusHari::LiburMutlak,
                $pengaturan,
                "Hari ini libur — {$liburMutlak->keterangan}.",
                $liburMutlak->keterangan,
            );
        }

        // 2) Kegiatan (rentang masuk) — hari dipaksa aktif, jam tetap dari pengaturan_hari
        if ($this->cariLibur($tanggal, 'masuk')) {
            if (! $pengaturan?->terkonfigurasi()) {
                return new HasilHariAktif(
                    StatusHari::PaksaAktif,
                    $pengaturan,
                    'Jam absensi hari ini belum dikonfigurasi.',
                );
            }

            return new HasilHariAktif(StatusHari::PaksaAktif, $pengaturan);
        }

        // 3) Libur rutin per pengaturan_hari
        if ($pengaturan?->libur()) {
            $keterangan = $pengaturan->keterangan ? " — {$pengaturan->keterangan}" : '';

            return new HasilHariAktif(
                StatusHari::LiburRutin,
                $pengaturan,
                "Hari ini libur sesuai pengaturan sekolah{$keterangan}.",
                $pengaturan->keterangan,
            );
        }

        // 4) Hari aktif — jam yang belum lengkap tetap memblokir scan
        if (! $pengaturan?->terkonfigurasi()) {
            return new HasilHariAktif(
                StatusHari::Aktif,
                $pengaturan,
                'Jam absensi hari ini belum dikonfigurasi.',
            );
        }

        return new HasilHariAktif(StatusHari::Aktif, $pengaturan);
    }

    /** Baris hari_libur aktif (is_active) yang mencakup tanggal, sesuai status masuk. */
    private function cariLibur(CarbonInterface $tanggal, string $statusMasuk): ?HariLibur
    {
        return HariLibur::aktif()
            ->where('status_masuk', $statusMasuk)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function (Builder $rentang) use ($tanggal) {
                $rentang
                    ->where(function (Builder $multi) use ($tanggal) {
                        $multi->whereNotNull('tanggal_selesai')
                            ->whereDate('tanggal_selesai', '>=', $tanggal);
                    })
                    ->orWhere(function (Builder $satuHari) use ($tanggal) {
                        $satuHari->whereNull('tanggal_selesai')
                            ->whereDate('tanggal_mulai', $tanggal);
                    });
            })
            ->orderByDesc('tanggal_mulai')
            ->first();
    }
}
