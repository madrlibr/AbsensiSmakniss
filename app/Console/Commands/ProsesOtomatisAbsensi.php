<?php

namespace App\Console\Commands;

use App\Models\PengaturanHari;
use App\Services\Absensi\AutoAlpa;
use App\Services\Absensi\AutoKabur;
use App\Services\Absensi\HariAktifRules;
use App\Services\Absensi\PrecreatePulang;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Task 12 — Job otomatis absensi (desain §7.5), dijadwalkan tiap menit.
 * Idempoten via cache key per tanggal: precreate:{T} · alpa:{T} · kabur:{T}.
 */
class ProsesOtomatisAbsensi extends Command
{
    protected $signature = 'absensi:proses-otomatis';

    protected $description = 'Job otomatis: pre-create pulang (belum_absen), auto-alpa (anti-join), auto-kabur — lewati hari libur';

    public function handle(
        HariAktifRules $hariAktif,
        PrecreatePulang $precreate,
        AutoAlpa $alpa,
        AutoKabur $kabur,
    ): int {
        $tanggal = Carbon::today();
        $hari = $hariAktif->status($tanggal);

        // Hari libur (mutlak/rutin) = nol aksi — desain §7.1
        if (! $hari->bolehDikerjakanJob()) {
            $this->info("Dilewati: {$hari->pesanBlokir}");

            return self::SUCCESS;
        }

        $pengaturan = $hari->pengaturan;

        if (! $pengaturan instanceof PengaturanHari || ! $pengaturan->terkonfigurasi()) {
            $this->info('Dilewati: jam absensi hari ini belum dikonfigurasi.');

            return self::SUCCESS;
        }

        $kini = Carbon::now();
        $kunci = $tanggal->toDateString();
        $ttl = now()->addHours(26);
        $lapor = ['precreate' => 0, 'alpa' => 0, 'kabur' => 0];

        // 1) Pre-create absensi_pulang saat gerbang dibuka
        if ($kini->gte($pengaturan->buka_scan_gerbang)
            && Cache::add("absensi:precreate:{$kunci}", 1, $ttl)) {
            $lapor['precreate'] = $precreate->jalankan($tanggal);
        }

        // 2) Auto-alpa pada jam_auto_alpa (anti-join gerbang hari ini)
        if ($kini->gte($pengaturan->jam_auto_alpa)
            && Cache::add("absensi:alpa:{$kunci}", 1, $ttl)) {
            $lapor['alpa'] = $alpa->jalankan($tanggal);
        }

        // 3) Auto-kabur setelah akhir jendela pulang + 1 menit (hanya yang hadir pagi)
        if ($kini->gte($pengaturan->akhir_scan_pulang->addMinute())
            && Cache::add("absensi:kabur:{$kunci}", 1, $ttl)) {
            $lapor['kabur'] = $kabur->jalankan($tanggal);
        }

        $this->info(sprintf(
            'Pre-create pulang: %d · Auto-alpa: %d · Auto-kabur: %d',
            $lapor['precreate'],
            $lapor['alpa'],
            $lapor['kabur'],
        ));

        return self::SUCCESS;
    }
}
