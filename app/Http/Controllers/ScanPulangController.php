<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPulang;
use App\Services\Absensi\HariAktifRules;
use App\Services\Absensi\HasilScan;
use App\Services\Absensi\ScanPulangService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Task 13 — Scanner pulang (tab kedua mockup scanner): endpoint update baris
 * pre-created → `pulang`, aksi piket `pulang_cepat` + kode karcis cetak.
 */
class ScanPulangController extends Controller
{
    public function index(HariAktifRules $hariAktif): View
    {
        $hari = $hariAktif->status(Carbon::today());

        $hariIni = AbsensiPulang::with('siswa')
            ->whereDate('tanggal', Carbon::today())
            ->orderByDesc('waktu_scan')
            ->orderByDesc('id')
            ->get();

        return view('scan.pulang', [
            'hari' => $hari,
            'pengaturan' => $hari->pengaturan,
            'sudahPulang' => $hariIni->filter(
                fn (AbsensiPulang $b) => in_array($b->status->value, ['pulang', 'pulang_cepat'], true),
            )->count(),
            'pulangCepat' => $hariIni->filter(fn (AbsensiPulang $b) => $b->status->value === 'pulang_cepat')->count(),
            'belumAbsen' => $hariIni->filter(fn (AbsensiPulang $b) => $b->status->value === 'belum_absen')->count(),
            'log' => $hariIni->take(10),
            'bolehManual' => ScanPulangService::petugasBolehManual(request()->user()),
        ]);
    }

    /** Endpoint JSON untuk kamera (QR), input manual & aksi karcis — dipanggil scan.js. */
    public function proses(Request $request, ScanPulangService $service): JsonResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:64'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $bolehManual = ScanPulangService::petugasBolehManual($user);

        $hasil = $service->proses(
            $data['kode'],
            $user,
            inputPetugas: $bolehManual && $request->boolean('manual'),
            keterangan: $data['keterangan'] ?? null,
            pulangCepat: $request->boolean('pulang_cepat'), // service menolak non-petugas dengan pesan jelas
        );

        return response()->json($this->payload($hasil), match ($hasil->jenis) {
            'tolak' => 422,
            default => 200,
        });
    }

    /** Halaman karcis pulang cepat (printable) untuk ditunjukkan ke penjaga gerbang. */
    public function karcis(AbsensiPulang $absensiPulang): View
    {
        return view('scan.karcis', [
            // Siswa tanpa relasi kelas — kelasAktif adalah accessor pivot TA aktif.
            'baris' => $absensiPulang->load('siswa', 'scannedBy'),
        ]);
    }

    private function payload(HasilScan $hasil): array
    {
        return [
            'jenis' => $hasil->jenis,
            'pesan' => $hasil->pesan,
            'siswa' => $hasil->siswa ? [
                'nama' => $hasil->siswa->nama,
                'nisn' => $hasil->siswa->nisn,
                'kelas' => $hasil->siswa->kelasAktif?->nama_kelas ?? '—',
                'jurusan' => $hasil->siswa->kelasAktif?->jurusan?->singkatan ?? '',
            ] : null,
            'status' => $hasil->baris?->status->value,
            'status_label' => $hasil->baris?->status->label(),
            'waktu' => $hasil->baris?->waktu_scan?->format('H:i:s'),
            'jumlah_orang_tua' => $hasil->siswa?->orangTua()->count() ?? 0,
            'karcis' => $hasil->baris?->kode_karcis ? [
                'kode' => $hasil->baris->kode_karcis,
                'url' => route('scan.pulang.karcis', $hasil->baris),
            ] : null,
        ];
    }
}
