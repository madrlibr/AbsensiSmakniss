<?php

namespace App\Services\Absensi;

use App\Enums\Role;
use App\Enums\StatusAbsensi;
use App\Models\AbsensiGerbang;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

/**
 * Task 11 — Proses scan gerbang masuk:
 * resolve siswa (payload QR "SISWA-{id}-{nisn}" atau NISN manual) →
 * cek baris hari ini (idempoten) → GerbangRules → tulis baris (unique siswa+tanggal).
 */
class ScanGerbangService
{
    public function __construct(private GerbangRules $gerbangRules) {}

    /**
     * @param  string  $kode  payload QR (SISWA-{id}-{nisn}) atau NISN (input manual).
     * @param  bool  $inputPetugas  input manual oleh piket/admin (boleh lewat jam tutup).
     * @param  string|null  $keterangan  wajib bila rules menandai keteranganWajib.
     */
    public function proses(string $kode, User $petugas, bool $inputPetugas = false, ?string $keterangan = null): HasilScan
    {
        $siswa = Siswa::cariIdentitas(trim($kode));

        if (! $siswa) {
            return HasilScan::tolak('Siswa tidak ditemukan — periksa NISN atau kartu QR.');
        }

        if (! $siswa->active) {
            return HasilScan::tolak("Siswa {$siswa->nama} tidak aktif — hubungi admin.", $siswa);
        }

        $tanggal = Carbon::today();

        // Idempoten: baris hari ini sudah ada?
        if ($baris = AbsensiGerbang::where('siswa_id', $siswa->id)->whereDate('tanggal', $tanggal)->first()) {
            return $this->cekBarisAda($baris, $siswa);
        }

        $hasil = $this->gerbangRules->tentukanStatus($tanggal->now(), $inputPetugas);

        if (! $hasil->izinkan) {
            return HasilScan::tolak($hasil->pesanBlokir, $siswa);
        }

        if ($hasil->keteranganWajib && trim((string) $keterangan) === '') {
            return HasilScan::tolak('Keterangan wajib untuk pencatatan setelah gerbang ditutup.', $siswa);
        }

        try {
            $baris = AbsensiGerbang::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $tanggal,
                'waktu_scan' => Carbon::now(),
                'status' => $hasil->status,
                'metode' => $inputPetugas ? 'manual' : 'qr',
                'scanned_by' => $petugas->id,
                'keterangan' => $keterangan,
            ]);
        } catch (QueryException) {
            // Race dua scan bersamaan pada detik yang sama — baris unik menang
            $baris = AbsensiGerbang::where('siswa_id', $siswa->id)->whereDate('tanggal', $tanggal)->firstOrFail();

            return HasilScan::info("Sudah absen jam {$baris->waktu_scan->format('H:i')}.", $siswa, $baris);
        }

        return HasilScan::sukses(
            "{$siswa->nama} · {$hasil->status->label()}",
            $siswa,
            $baris,
        );
    }

    /* ── Bantuan ───────────────────────────────────────────── */

    /** Baris hari ini sudah ada — jangan pernah dobel (aturan §7.2). */
    private function cekBarisAda(AbsensiGerbang $baris, Siswa $siswa): HasilScan
    {
        return match ($baris->status) {
            StatusAbsensi::HadirTepatWaktu, StatusAbsensi::Terlambat => HasilScan::info(
                "Sudah absen jam {$baris->waktu_scan->format('H:i')} ({$baris->status->label()}).",
                $siswa,
                $baris,
            ),
            StatusAbsensi::Alpa => HasilScan::tolak(
                'Status alpa hari ini — koreksi hanya melalui menu Koreksi Absensi.',
                $siswa,
            ),
            default => HasilScan::tolak( // izin / sakit
                "Siswa berstatus {$baris->status->label()} hari ini.",
                $siswa,
            ),
        };
    }

    /** Role yang boleh memakai jalan pintas input manual setelah jam tutup. */
    public static function petugasBolehManual(User $user): bool
    {
        return in_array($user->role, [Role::Admin, Role::Piket], true);
    }
}
