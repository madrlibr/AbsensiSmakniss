<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatusAbsensi;
use App\Models\AbsensiGerbang;
use App\Services\Absensi\HariAktifRules;
use App\Services\Absensi\HasilScan;
use App\Services\Absensi\ScanGerbangService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Task 11 — Scanner gerbang masuk (kamera QR + input manual petugas).
 * Halaman mengikuti mockup `scanner_gerbang_presensi_siswa_smk`.
 */
class ScanGerbangController extends Controller
{
    public function index(HariAktifRules $hariAktif): View
    {
        $hari = $hariAktif->status(Carbon::today());

        $hariIni = AbsensiGerbang::with('siswa')
            ->whereDate('tanggal', Carbon::today())
            ->orderByDesc('waktu_scan')
            ->orderByDesc('id')
            ->get();        return view('scan.gerbang', [
            'hari' => $hari,
            'pengaturan' => $hari->pengaturan,
            'tepatWaktu' => $hariIni->where('status', StatusAbsensi::HadirTepatWaktu)->count(),
            'terlambat' => $hariIni->where('status', StatusAbsensi::Terlambat)->count(),
            'totalHadir' => $hariIni->count(),
            'log' => $hariIni->take(10),
            'bolehManual' => ScanGerbangService::petugasBolehManual(request()->user()),
        ]);
    }

    /** Endpoint JSON untuk kamera (QR) & input manual — dipanggil scan.js. */
    public function proses(Request $request, ScanGerbangService $service): JsonResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:64'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $hasil = $service->proses(
            $data['kode'],
            $user,
            inputPetugas: ScanGerbangService::petugasBolehManual($user) && $this->inputManual($request),
            keterangan: $data['keterangan'] ?? null,
        );

        return response()->json($this->payload($hasil), match ($hasil->jenis) {
            'tolak' => 422,
            default => 200,
        });
    }

    private function inputManual(Request $request): bool
    {
        return $request->boolean('manual');
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
        ];
    }
}
