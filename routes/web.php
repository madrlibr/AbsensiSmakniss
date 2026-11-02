<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\HalamanController;
use App\Http\Controllers\HariLiburController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KartuPelajarController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\PengaturanHariController;
use App\Http\Controllers\ScanGerbangController;
use App\Http\Controllers\ScanPulangController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TahunAjaranController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tamu (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.attempt');
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/', fn () => redirect()->intended(route(auth()->check() ? 'dashboard' : 'login')));

/*
|--------------------------------------------------------------------------
| Rute terproteksi — 'role:…' mengikuti matriks menu/desain §4
|--------------------------------------------------------------------------
Placeholder untuk modul berikutnya (lihat tasks/todo.md): nama rute sudah final
sejak awal sehingga menu & tautan internal bisa diuji sejak Task 4.
*/
Route::middleware('auth')->group(function () {

    // ── Operasional (Task 11–14, 21) ────────────────────────────────────
    Route::middleware('role:admin,guru,wali_kelas,bk,osis,piket')->group(function () {
        Route::get('dashboard', [HalamanController::class, 'dashboard'])->name('dashboard');
    });

    Route::middleware('role:admin,osis,piket')->group(function () {
        // Task 11 — Scanner gerbang masuk (kamera QR + input manual petugas)
        Route::get('scan/gerbang', [ScanGerbangController::class, 'index'])->name('scan.gerbang');
        Route::post('scan/gerbang', [ScanGerbangController::class, 'proses'])->name('scan.gerbang.proses');
        // Task 13 — Scanner pulang + karcis pulang cepat
        Route::get('scan/pulang', [ScanPulangController::class, 'index'])->name('scan.pulang');
        Route::post('scan/pulang', [ScanPulangController::class, 'proses'])->name('scan.pulang.proses');
        Route::get('scan/pulang/karcis/{absensi_pulang}', [ScanPulangController::class, 'karcis'])->name('scan.pulang.karcis');
    });

    Route::middleware('role:admin,guru,wali_kelas')->group(function () {
        Route::get('absensi/mapel', [HalamanController::class, 'absensiMapel'])->name('absensi.mapel');
    });

    // ── Laporan & administrasi (Task 15, 16, 19, 21) ─────────────────────
    Route::middleware('role:admin,wali_kelas,bk')->group(function () {
        Route::get('rekap', [HalamanController::class, 'rekap'])->name('rekap.index');
    });

    Route::middleware('role:admin,bk')->group(function () {
        Route::get('rekap/laporan-bk', [HalamanController::class, 'laporanBk'])->name('laporan.bk');
    });

    Route::middleware('role:admin,wali_kelas')->group(function () {
        Route::get('pengajuan', [HalamanController::class, 'pengajuan'])->name('pengajuan.index');
    });

    Route::middleware('role:admin,wali_kelas,bk,guru,osis,piket')->group(function () {
        Route::get('koreksi', [HalamanController::class, 'koreksi'])->name('koreksi.index');
    });

    // ── Master data (Task 6–8) ───────────────────────────────────────────
    Route::middleware('role:admin')->prefix('master')->name('master.')->group(function () {
        // Task 6 — Tahun Ajaran (switch aktif)
        Route::get('tahun-ajaran', [TahunAjaranController::class, 'index'])->name('tahun-ajaran');
        Route::get('tahun-ajaran/create', [TahunAjaranController::class, 'create'])->name('tahun-ajaran.create');
        Route::post('tahun-ajaran', [TahunAjaranController::class, 'store'])->name('tahun-ajaran.store');
        Route::get('tahun-ajaran/{tahunAjaran}/edit', [TahunAjaranController::class, 'edit'])->name('tahun-ajaran.edit');
        Route::put('tahun-ajaran/{tahunAjaran}', [TahunAjaranController::class, 'update'])->name('tahun-ajaran.update');
        Route::delete('tahun-ajaran/{tahunAjaran}', [TahunAjaranController::class, 'destroy'])->name('tahun-ajaran.destroy');
        Route::post('tahun-ajaran/{tahunAjaran}/aktifkan', [TahunAjaranController::class, 'jadikanAktif'])->name('tahun-ajaran.aktifkan');

        // Task 6 — Jurusan
        Route::get('jurusan', [JurusanController::class, 'index'])->name('jurusan');
        Route::get('jurusan/create', [JurusanController::class, 'create'])->name('jurusan.create');
        Route::post('jurusan', [JurusanController::class, 'store'])->name('jurusan.store');
        Route::get('jurusan/{jurusan}/edit', [JurusanController::class, 'edit'])->name('jurusan.edit');
        Route::put('jurusan/{jurusan}', [JurusanController::class, 'update'])->name('jurusan.update');
        Route::delete('jurusan/{jurusan}', [JurusanController::class, 'destroy'])->name('jurusan.destroy');

        // Task 6 — Mata Pelajaran
        Route::get('mapel', [MapelController::class, 'index'])->name('mapel');
        Route::get('mapel/create', [MapelController::class, 'create'])->name('mapel.create');
        Route::post('mapel', [MapelController::class, 'store'])->name('mapel.store');
        Route::get('mapel/{mapel}/edit', [MapelController::class, 'edit'])->name('mapel.edit');
        Route::put('mapel/{mapel}', [MapelController::class, 'update'])->name('mapel.update');
        Route::delete('mapel/{mapel}', [MapelController::class, 'destroy'])->name('mapel.destroy');

        // Task 6 — Guru
        Route::get('guru', [GuruController::class, 'index'])->name('guru');
        Route::get('guru/create', [GuruController::class, 'create'])->name('guru.create');
        Route::post('guru', [GuruController::class, 'store'])->name('guru.store');
        Route::get('guru/{guru}/edit', [GuruController::class, 'edit'])->name('guru.edit');
        Route::put('guru/{guru}', [GuruController::class, 'update'])->name('guru.update');
        Route::delete('guru/{guru}', [GuruController::class, 'destroy'])->name('guru.destroy');

        // Task 7 — Kelas (TA + jurusan + wali kelas + anggota via pivot)
        Route::get('kelas', [KelasController::class, 'index'])->name('kelas');
        Route::get('kelas/create', [KelasController::class, 'create'])->name('kelas.create');
        Route::post('kelas', [KelasController::class, 'store'])->name('kelas.store');
        Route::get('kelas/{kelas}/edit', [KelasController::class, 'edit'])->name('kelas.edit');
        Route::put('kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
        Route::delete('kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');
        Route::get('kelas/{kelas}/anggota', [KelasController::class, 'anggota'])->name('kelas.anggota');
        Route::post('kelas/{kelas}/anggota', [KelasController::class, 'tambahAnggota'])->name('kelas.anggota.tambah');
        Route::put('siswa-kelas/{siswaKelas}/pindah', [KelasController::class, 'pindahAnggota'])->name('kelas.anggota.pindah');
        Route::delete('siswa-kelas/{siswaKelas}', [KelasController::class, 'keluarkanAnggota'])->name('kelas.anggota.keluarkan');
        Route::get('kelas/{kelas}/kartu', [KartuPelajarController::class, 'perKelas'])->name('kelas.kartu');

        // Task 7 — Siswa (identitas + QR kartu pelajar)
        Route::get('siswa', [SiswaController::class, 'index'])->name('siswa');
        Route::get('siswa/create', [SiswaController::class, 'create'])->name('siswa.create');
        Route::post('siswa', [SiswaController::class, 'store'])->name('siswa.store');
        Route::get('siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit');
        Route::put('siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
        Route::delete('siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
        Route::get('siswa/{siswa}/kartu', [KartuPelajarController::class, 'perSiswa'])->name('siswa.kartu');
    });

    // ── Pengaturan (Task 8–9) ────────────────────────────────────────────
    // Task 8 — Jadwal pelajaran: wali kelas hanya melihat, admin mengelola
    Route::get('jadwal', [JadwalPelajaranController::class, 'index'])
        ->middleware('role:admin,wali_kelas')
        ->name('jadwal.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('jadwal/create', [JadwalPelajaranController::class, 'create'])->name('jadwal.create');
        Route::post('jadwal', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
        Route::get('jadwal/{jadwal}/edit', [JadwalPelajaranController::class, 'edit'])->name('jadwal.edit');
        Route::put('jadwal/{jadwal}', [JadwalPelajaranController::class, 'update'])->name('jadwal.update');
        Route::delete('jadwal/{jadwal}', [JadwalPelajaranController::class, 'destroy'])->name('jadwal.destroy');
        // Task 9 — Pengaturan jam per hari + hari libur rentang
        Route::get('pengaturan/hari', [PengaturanHariController::class, 'index'])->name('pengaturan.hari');
        Route::post('pengaturan/hari', [PengaturanHariController::class, 'simpan'])->name('pengaturan.hari.simpan');
        Route::post('pengaturan/hari/reset', [PengaturanHariController::class, 'resetDefault'])->name('pengaturan.hari.reset');
        Route::get('pengaturan/libur', [HariLiburController::class, 'index'])->name('pengaturan.libur');
        Route::get('pengaturan/libur/create', [HariLiburController::class, 'create'])->name('pengaturan.libur.create');
        Route::post('pengaturan/libur', [HariLiburController::class, 'store'])->name('pengaturan.libur.store');
        Route::get('pengaturan/libur/{hariLibur}/edit', [HariLiburController::class, 'edit'])->name('pengaturan.libur.edit');
        Route::put('pengaturan/libur/{hariLibur}', [HariLiburController::class, 'update'])->name('pengaturan.libur.update');
        Route::delete('pengaturan/libur/{hariLibur}', [HariLiburController::class, 'destroy'])->name('pengaturan.libur.destroy');
    });

    // ── Portal orang tua (Task 17) ───────────────────────────────────────
    Route::middleware('role:orang_tua')->prefix('portal')->group(function () {
        Route::get('/', [HalamanController::class, 'portal'])->name('portal.index');
        Route::get('riwayat', [HalamanController::class, 'portalRiwayat'])->name('portal.riwayat');
        Route::get('pengajuan', [HalamanController::class, 'portalPengajuan'])->name('portal.pengajuan');
        Route::get('notifikasi', [HalamanController::class, 'portalNotifikasi'])->name('portal.notifikasi');
    });
});

// Galeri komponen UI — hanya environment lokal/pengujian (Task 4).
if (app()->environment(['local', 'testing'])) {
    Route::get('ui', [HalamanController::class, 'demo'])->name('ui.demo');
}
