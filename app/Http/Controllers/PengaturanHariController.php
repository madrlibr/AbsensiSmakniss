<?php

namespace App\Http\Controllers;

use App\Enums\Hari;
use App\Models\PengaturanHari;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Editor pengaturan jam presensi per hari (Task 9) — mengikuti mockup
 * `pengaturan_jam_absensi`: 7 baris (Senin–Minggu), semua jam dikustom,
 * toggle masuk/libur per hari = modul hari libur rutin.
 */
class PengaturanHariController extends Controller
{
    /** Urutan kolom jam untuk rantai validasi waktu. */
    private const rantaiWaktu = [
        'buka_scan_gerbang' => 'Buka scan gerbang',
        'batas_tepat_waktu' => 'Batas tepat waktu',
        'jam_tutup_gerbang' => 'Tutup gerbang masuk',
        'jam_auto_alpa' => 'Jam auto-alpa',
    ];

    public function index(): View
    {
        // firstOrCreate agar 7 baris selalu siap walau DB lama belum ter-seed
        foreach (Hari::semua() as $hari) {
            PengaturanHari::firstOrCreate(['hari' => $hari->value], PengaturanHari::defaultUntuk($hari));
        }

        // Urut Senin→Minggu di aplikasi (FIELD() MySQL tak ada di SQLite saat test)
        $urutan = collect(Hari::semua())->pluck('value')->flip();
        $semua = PengaturanHari::all()
            ->sortBy(fn (PengaturanHari $p) => $urutan[$p->hari->value] ?? 9)
            ->values();

        return view('pengaturan.hari', [
            'semua' => $semua,
            'ringkasan' => $semua->firstWhere('status_hari', 'masuk'),
            'bidang' => [
                'buka_scan_gerbang' => ['label' => 'Buka Scan Gerbang', 'type' => 'time', 'hint' => 'Gerbang mulai menerima scan masuk.'],
                'batas_tepat_waktu' => ['label' => 'Batas Tepat Waktu', 'type' => 'time', 'hint' => 'Scan sebelum jam ini = hadir tepat waktu.'],
                'toleransi_menit' => ['label' => 'Toleransi (menit)', 'type' => 'number', 'hint' => 'Scan masih diterima sampai tutup gerbang.'],
                'jam_tutup_gerbang' => ['label' => 'Tutup Gerbang Masuk', 'type' => 'time', 'hint' => 'Setelah ini scan masuk dikunci — input manual petugas piket.'],
                'jam_auto_alpa' => ['label' => 'Jam Auto-Alpa', 'type' => 'time', 'hint' => 'Siswa pagi tanpa tap otomatis ditandai alpa.'],
                'mulai_scan_pulang' => ['label' => 'Mulai Scan Pulang', 'type' => 'time', 'hint' => 'Awal jendela scan pulang.'],
                'akhir_scan_pulang' => ['label' => 'Akhir Scan Pulang', 'type' => 'time', 'hint' => 'Setelah ini pulang tanpa tap = kabur / belum absen.'],
            ],
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hari' => ['required', 'array', 'size:7'],
            'hari.*.status_hari' => ['required', 'in:masuk,libur'],
            'hari.*.buka_scan_gerbang' => ['nullable', 'date_format:H:i'],
            'hari.*.batas_tepat_waktu' => ['nullable', 'date_format:H:i'],
            'hari.*.toleransi_menit' => ['nullable', 'integer', 'min:0', 'max:180'],
            'hari.*.jam_tutup_gerbang' => ['nullable', 'date_format:H:i'],
            'hari.*.jam_auto_alpa' => ['nullable', 'date_format:H:i'],
            'hari.*.mulai_scan_pulang' => ['nullable', 'date_format:H:i'],
            'hari.*.akhir_scan_pulang' => ['nullable', 'date_format:H:i'],
            'hari.*.keterangan' => ['nullable', 'string', 'max:120'],
        ]);

        if ($pesan = $this->cekUrutanWaktu($data['hari'])) {
            return back()->withInput()->with('gagal', $pesan);
        }

        foreach ($data['hari'] as $kunci => $isi) {
            PengaturanHari::updateOrCreate(
                ['hari' => $kunci],
                $isi + ['toleransi_menit' => $isi['toleransi_menit'] ?? 15],
            );
        }

        return redirect()
            ->route('pengaturan.hari')
            ->with('sukses', 'Pengaturan jam presensi semua hari tersimpan.');
    }

    public function resetDefault(): RedirectResponse
    {
        PengaturanHari::kembalikanDefault();

        return redirect()
            ->route('pengaturan.hari')
            ->with('sukses', 'Pengaturan dikembalikan ke default: Senin–Jumat masuk 06:45–15:30, Sabtu & Minggu libur.');
    }

    /* ── Validasi ──────────────────────────────────────────── */

    /**
     * Pastikan rantai waktu berurutan: buka ≤ batas ≤ tutup ≤ auto-alpa,
     * dan jendela pulang: mulai < akhir. Banding hanya bila keduanya terisi.
     *
     * @param  array<string, array<string, ?string>>  $hari
     */
    private function cekUrutanWaktu(array $hari): ?string
    {
        $labelHari = collect(Hari::semua())->mapWithKeys(fn (Hari $h) => [$h->value => $h->label()]);

        foreach ($hari as $kunci => $isi) {
            $deret = array_map(fn (string $kolom) => $isi[$kolom] ?? null, array_keys(self::rantaiWaktu));

            for ($i = 0; $i < count($deret) - 1; $i++) {
                [$a, $b] = [$deret[$i], $deret[$i + 1]];
                if ($a !== null && $b !== null && $b < $a) {
                    return sprintf(
                        '%s: %s (%s) harus berurutan sebelum %s (%s).',
                        $labelHari[$kunci] ?? $kunci,
                        array_values(self::rantaiWaktu)[$i], $a,
                        array_values(self::rantaiWaktu)[$i + 1], $b,
                    );
                }
            }

            $mulai = $isi['mulai_scan_pulang'] ?? null;
            $akhir = $isi['akhir_scan_pulang'] ?? null;
            if ($mulai !== null && $akhir !== null && $akhir <= $mulai) {
                return sprintf(
                    '%s: akhir scan pulang (%s) harus setelah mulai scan pulang (%s).',
                    $labelHari[$kunci] ?? $kunci, $akhir, $mulai,
                );
            }
        }

        return null;
    }
}
