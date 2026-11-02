<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Rute placeholder: menampilkan halaman "sedang disiapkan" berbingkai shell
 * sampai modulnya dibuat (lihat tasks/todo.md). Setelah modul jadi, method
 * terkait diganti oleh controller sebenarnya dan rutenya dialihkan.
 */
class HalamanController extends Controller
{
    public function dashboard(): View
    {
        return $this->soon('Dashboard Monitoring', 'dashboard', 'Dashboard admin/KPI dan ringkasan hari ini dibuat pada Task 21.');
    }

    public function scanGerbang(): View
    {
        return $this->soon('Scanner Gerbang', 'qr_code_scanner', 'Halaman scan QR gerbang masuk dibuat pada Task 11.');
    }

    public function scanPulang(): View
    {
        return $this->soon('Scanner Pulang', 'logout', 'Halaman scan pulang & karcis piket dibuat pada Task 13.');
    }

    public function absensiMapel(): View
    {
        return $this->soon('Presensi Mata Pelajaran', 'menu_book', 'Presensi mapel oleh guru dibuat pada Task 14.');
    }

    public function rekap(): View
    {
        return $this->soon('Rekap Kehadiran', 'description', 'Rekap harian/mingguan/bulanan dibuat pada Task 19.');
    }

    public function laporanBk(): View
    {
        return $this->soon('Laporan BK', 'fact_check', 'Laporan siswa bermasalah (<90%) dibuat pada Task 21.');
    }

    public function pengajuan(): View
    {
        return $this->soon('Pengajuan Izin & Sakit', 'assignment_turned_in', 'Antrean review pengajuan dibuat pada Task 15.');
    }

    public function koreksi(): View
    {
        return $this->soon('Koreksi Data', 'rule', 'Workflow koreksi absensi dibuat pada Task 16.');
    }

    // masterMapel/masterKelas/masterSiswa/masterGuru dihapus — modul CRUD
    // sudah pindah ke {Mapel,Kelas,Siswa,Guru}Controller (Task 6–7).

    // jadwal() dihapus — modul jadwal pindah ke JadwalPelajaranController (Task 8).

    // pengaturanHari()/pengaturanLibur() dihapus — modul pindah ke
    // {PengaturanHari,HariLibur}Controller (Task 9).

    public function portal(): View
    {
        return $this->soon('Portal Orang Tua', 'home', 'Portal ortu (timeline 3 titik) dibuat pada Task 17.');
    }

    public function portalRiwayat(): View
    {
        return $this->soon('Riwayat Absensi Anak', 'event_note', 'Riwayat per anak dibuat pada Task 17.');
    }

    public function portalPengajuan(): View
    {
        return $this->soon('Ajukan Izin & Sakit', 'edit_document', 'Form pengajuan ortu dibuat pada Task 15.');
    }

    public function portalNotifikasi(): View
    {
        return $this->soon('Notifikasi Kehadiran', 'notifications', 'Daftar notifikasi dibuat pada Task 17–18.');
    }

    /** Galeri komponen x-ui/* (hanya lokal). */
    public function demo(): View
    {
        // Demo toast: /ui?toast=sukses atau /ui?toast=gagal
        if ($jenis = request()->query('toast')) {
            session()->flash(
                in_array($jenis, ['sukses', 'gagal'], true) ? $jenis : 'info',
                $jenis === 'gagal'
                    ? 'Contoh pesan gagal — data tidak dapat disimpan.'
                    : 'Perubahan berhasil disimpan.'
            );
        }

        return view('ui.demo');
    }

    private function soon(string $judul, string $ikon, string $pesan): View
    {
        return view('halaman.soon', ['judul' => $judul, 'ikon' => $ikon, 'pesan' => $pesan]);
    }
}
