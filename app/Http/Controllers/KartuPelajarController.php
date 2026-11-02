<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use Dompdf\Dompdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Cetak lembar QR kartu pelajar (Task 7).
 * dompdf + endroid/qr-code (framework-agnostic) — QR di-embed sebagai data URI.
 */
class KartuPelajarController extends Controller
{
    public function perKelas(Kelas $kelas): Response|RedirectResponse
    {
        $anggota = Siswa::query()
            ->aktif()
            ->whereHas('riwayatKelas', fn ($q) => $q
                ->where('kelas_id', $kelas->id)
                ->where('is_active', true))
            ->orderBy('nama')
            ->get();

        if ($anggota->isEmpty()) {
            return back()->with('gagal', "Kelas {$kelas->nama_kelas} belum memiliki anggota untuk dicetak.");
        }

        return $this->unduh($anggota, 'kartu-pelajar-' . $this->slug($kelas->nama_kelas));
    }

    public function perSiswa(Siswa $siswa): Response
    {
        return $this->unduh(collect([$siswa]), 'kartu-pelajar-' . $this->slug($siswa->nisn));
    }

    /** Susun lembar A4 berisi kartu-kartu pelajar lalu stream sebagai PDF. */
    private function unduh($siswa, string $namaBerkas): Response
    {
        $writer = new PngWriter();

        $kartu = $siswa->map(function (Siswa $s) use ($writer): array {
            // endroid/qr-code v6: konfigurasi lewat constructor (class readonly)
            $qr = $writer->write(
                new QrCode(data: $s->qr_code, size: 240, margin: 8)
            );

            return [
                'siswa' => $s,
                'qr' => $qr->getDataUri(),
            ];
        })->values();

        $html = view('pdf.kartu_pelajar', [
            'kartu' => $kartu,
            'sekolah' => config('app.name'),
        ])->render();

        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$namaBerkas}.pdf\"",
        ]);
    }

    private function slug(string $teks): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $teks), '-'));
    }
}
