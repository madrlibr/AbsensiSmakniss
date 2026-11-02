<?php

namespace App\Services\Absensi;

use App\Enums\Role;
use App\Enums\StatusAbsensi;
use App\Models\AbsensiPulang;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;

/**
 * Task 13 — Proses scan pulang: update baris PRE-CREATED `absensi_pulang`
 * (desain §7.4): dalam jendela → `pulang`; piket set `pulang_cepat` kapan pun
 * (wajib keterangan, dapat `kode_karcis`); baris final (sudah pulang/kabur)
 * tidak bisa diubah lewat scan.
 */
class ScanPulangService
{
    public function __construct(
        private PulangRules $pulangRules,
        private HariAktifRules $hariAktif,
    ) {}

    /**
     * @param  bool  $inputPetugas  input manual piket/admin (tetap dibatasi jendela).
     * @param  bool  $pulangCepat  aksi piket: kapan pun, wajib keterangan + karcis.
     */
    public function proses(
        string $kode,
        User $petugas,
        bool $inputPetugas = false,
        ?string $keterangan = null,
        bool $pulangCepat = false,
    ): HasilScan {
        $siswa = Siswa::cariIdentitas(trim($kode));

        if (! $siswa) {
            return HasilScan::tolak('Siswa tidak ditemukan — periksa NISN atau kartu QR.');
        }

        if (! $siswa->active) {
            return HasilScan::tolak("Siswa {$siswa->nama} tidak aktif — hubungi admin.", $siswa);
        }

        // Hari libur (mutlak/rutin) atau jam belum dikonfigurasi — semua jalur ditolak
        $hari = $this->hariAktif->status(Carbon::today());

        if ($pesan = $hari->pesanBlokir) {
            return HasilScan::tolak($pesan, $siswa);
        }

        $baris = AbsensiPulang::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', Carbon::today())
            ->first();

        if (! $baris) {
            return HasilScan::tolak('Baris pulang siswa hari ini tidak ditemukan — pastikan job harian sudah berjalan.', $siswa);
        }

        // Baris final — idempoten (sudah pulang) / tidak bisa diubah scan (kabur)
        if (in_array($baris->status, [StatusAbsensi::Pulang, StatusAbsensi::PulangCepat], true)) {
            return HasilScan::info(
                "Sudah pulang jam {$baris->waktu_scan?->format('H:i')} ({$baris->status->label()}).",
                $siswa,
                $baris,
            );
        }

        if ($baris->status === StatusAbsensi::Kabur) {
            return HasilScan::tolak('Status kabur — koreksi hanya melalui menu Koreksi Absensi.', $siswa);
        }

        // Aksi piket pulang_cepat — kapan pun pada hari aktif
        if ($pulangCepat) {
            if (! $petugas->role->bolehInputManual()) {
                return HasilScan::tolak('Hanya petugas piket/admin yang dapat mengatur pulang cepat.', $siswa);
            }

            if (trim((string) $keterangan) === '') {
                return HasilScan::tolak('Keterangan wajib untuk pencatatan pulang cepat.', $siswa);
            }

            $baris->update([
                'waktu_scan' => Carbon::now(),
                'status' => StatusAbsensi::PulangCepat,
                'metode' => 'manual',
                'scanned_by' => $petugas->id,
                'kode_karcis' => $this->kodeKarcis($siswa),
                'keterangan' => $keterangan,
            ]);

            return HasilScan::sukses(
                "{$siswa->nama} · Pulang Cepat · Karcis {$baris->kode_karcis}",
                $siswa,
                $baris,
            );
        }

        // Scan normal — aturan jendela pulang (§7.4)
        $hasil = $this->pulangRules->dalamJendela(Carbon::now());

        if (! $hasil->izinkan) {
            return HasilScan::tolak($hasil->pesanBlokir, $siswa);
        }

        $baris->update([
            'waktu_scan' => Carbon::now(),
            'status' => StatusAbsensi::Pulang,
            'metode' => $inputPetugas ? 'manual' : 'qr',
            'scanned_by' => $petugas->id,
        ]);

        return HasilScan::sukses("{$siswa->nama} · Pulang", $siswa, $baris);
    }

    /** Kode karcis deterministik & unik: KC-{YYMMDD}-{NISN} (nisn & baris unik). */
    private function kodeKarcis(Siswa $siswa): string
    {
        return sprintf('KC-%s-%s', Carbon::today()->format('ymd'), $siswa->nisn);
    }

    /** Role yang boleh memakai input manual & aksi pulang cepat. */
    public static function petugasBolehManual(User $user): bool
    {
        return $user->role->bolehInputManual();
    }
}
