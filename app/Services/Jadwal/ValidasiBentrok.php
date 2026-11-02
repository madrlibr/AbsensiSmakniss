<?php

namespace App\Services\Jadwal;

use App\Models\JadwalPelajaran;

/**
 * Aturan anti-bentrok jadwal pelajaran (Task 8).
 *
 * Urutan pemeriksaan: jam tidak valid → kelas → guru → ruangan.
 * Kandidat bentrok disaring dulu pada (TA, hari, semester) lalu ditentukan oleh
 * jam ke yang sama ATAU rentang waktu yang tumpang tindih.
 * Waktu dinormalisasi ke format H:i:s agar perbandingan aman di MySQL (TIME)
 * maupun SQLite (teks).
 */
class ValidasiBentrok
{
    /**
     * @param  array{tahun_ajaran_id: int, kelas_id: int, guru_id: int, hari: string, jam_ke: int, jam_mulai: string, jam_selesai: string, ruangan?: ?string, semester: string}  $data
     * @return string|null Pesan bentrok pertama, atau null bila lolos.
     */
    public function cek(array $data, ?JadwalPelajaran $abaikan = null): ?string
    {
        $mulai = $this->normal($data['jam_mulai']);
        $selesai = $this->normal($data['jam_selesai']);

        if ($selesai <= $mulai) {
            return 'Jam selesai harus setelah jam mulai.';
        }

        $dasar = fn ($q) => $q
            ->where('tahun_ajaran_id', $data['tahun_ajaran_id'])
            ->where('hari', $data['hari'])
            ->where('semester', $data['semester'])
            ->when($abaikan, fn ($q) => $q->whereKeyNot($abaikan->id));

        $tumpangTindih = fn ($q) => $q
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai);

        $cari = fn (string $kolom, mixed $nilai): ?JadwalPelajaran => JadwalPelajaran::query()
            ->where($kolom, $nilai)
            ->where(fn ($q) => $q
                ->where('jam_ke', $data['jam_ke'])
                ->orWhere($tumpangTindih))
            ->where($dasar)
            ->first();

        // 1) Bentrok kelas — satu kelas tak boleh dobel di jam ke sama / jam tumpang tindih
        $bentrokKelas = $cari('kelas_id', $data['kelas_id']);
        if ($bentrokKelas) {
            return sprintf(
                'Bentrok kelas: %s sudah diisi %s jam ke-%d pada %s (jam %s–%s).',
                $bentrokKelas->kelas?->nama_kelas,
                $bentrokKelas->mapel?->nama_mapel,
                $bentrokKelas->jam_ke,
                $bentrokKelas->hari->label(),
                $bentrokKelas->jam_mulai->format('H:i'),
                $bentrokKelas->jam_selesai->format('H:i'),
            );
        }

        // 2) Bentrok guru — satu guru tak boleh mengajar di dua tempat sekaligus
        $bentrokGuru = $cari('guru_id', $data['guru_id']);
        if ($bentrokGuru) {
            return sprintf(
                'Bentrok guru: %s sudah mengajar %s untuk %s jam ke-%d pada %s.',
                $bentrokGuru->guru?->nama,
                $bentrokGuru->mapel?->nama_mapel,
                $bentrokGuru->kelas?->nama_kelas,
                $bentrokGuru->jam_ke,
                $bentrokGuru->hari->label(),
            );
        }

        // 3) Bentrok ruangan — satu ruang tak boleh dipakai dua kelas bersamaan
        $ruangan = trim((string) ($data['ruangan'] ?? ''));
        if ($ruangan !== '') {
            $bentrokRuang = $cari('ruangan', $ruangan);
            if ($bentrokRuang) {
                return sprintf(
                    'Bentrok ruangan: %s sudah dipakai %s (%s) jam ke-%d pada %s.',
                    $ruangan,
                    $bentrokRuang->mapel?->nama_mapel,
                    $bentrokRuang->kelas?->nama_kelas,
                    $bentrokRuang->jam_ke,
                    $bentrokRuang->hari->label(),
                );
            }
        }

        return null;
    }

    /** "07:00" → "07:00:00" (format kolom TIME). */
    private function normal(string $waktu): string
    {
        $waktu = trim($waktu);

        return strlen($waktu) === 5 ? $waktu . ':00' : $waktu;
    }
}
