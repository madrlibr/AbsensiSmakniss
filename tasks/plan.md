# Implementation Plan: Sistem Absensi Siswa SMK (UJIKOM)

## Overview

Membangun sistem absensi SMK berbasis Laravel 13 + PHP 8.5 + MySQL + Tailwind v4 + Blade, sesuai
`Planner/DESAIN_SISTEM_ABSENSI.md` (REVISI 3). Tiga jenis absensi (gerbang, mapel, pulang) dengan
setting jam **per hari**, auto-alpa/auto-kabur via scheduler, approval izin ortu & koreksi,
rekap + ekspor PDF/XLSX, dan notifikasi log berantre.

## Architecture Decisions

- **Auth manual** (session + middleware role + Gate), tanpa starter kit — UI mengikuti design system
  `Planner/PrdAndDesign/smk_attendance_os/DESIGN.md`.
- **Design system 1:1 dengan mockup** (Task 4): token Tailwind v4 (`resources/css/app.css` `@theme`)
  memakai **nama class identik** dengan `PrdAndDesign/*/code.html` (`bg-surface-container-lowest`,
  `text-headline-lg`, `p-space-md`, `font-title-md`…) sehingga markup mockup di-port nyaris verbatim.
  Font Plus Jakarta Sans + Inter, ikon Material Symbols (CDN Google Fonts, sama seperti mockup).
  Komponen Blade `x-ui/*` = distilasi markup mockup; shell `layouts/app.blade.php` = sidebar `w-72` + topbar.
- **Rule engine terpusat** `App\Services\Absensi\{HariAktifRules, GerbangRules, PulangRules}` —
  satu-satunya tempat logika waktu dipakai controller & job (mudah diuji).
- **Setting per hari** di `pengaturan_hari` (7 baris) + `hari_libur` rentang; precedence tertulis di
  desain §7.1. Job otomatis berjalan **tiap menit** dan dicek terhadap setting hari + cache key
  idempoten (`alpa:{tanggal}`, `kabur:{tanggal}`, `precreate:{tanggal}`) — karena jam bersifat dinamis.
- **Baris pulang pre-created** `belum_absen` tiap hari aktif; gerbang dibuat saat scan/approve/alpa.
- **Polymorphic reference** (`absensi_type` + `absensi_id`) untuk koreksi & notifikasi.
- **Notifikasi** via `App\Services\Notifikasi\NotifikasiGateway` interface → `DatabaseGateway`
  (log + antrean queue database); siap diswap ke WA gateway.
- **Paket:** `dompdf/dompdf` (ekspor PDF), `maatwebsite/excel` (ekspor XLSX), `endroid/qr-code`
  (cetak kartu pelajar), `html5-qrcode` via npm (scan kamera di browser).
- **Timezone** `Asia/Jakarta` di `.env` — krusial, semua jam absensi bergantung padanya.

## Task List

### Phase 1: Fondasi
- [x] Task 1: Environment MySQL, timezone, scheduler, paket
- [x] Task 2: Migrasi & model master (users/role, tahun_ajaran, jurusan, mapel, guru)
- [x] Task 3: Migrasi & model siswa/kelas (siswa, siswa_kelas, orang_tua, siswa_orang_tua)
- [x] Task 4: Fondasi UI/UX — design tokens Tailwind v4, shell layout, komponen Blade `x-ui/*`
- [x] Task 5: Auth login + guard role + menu per role

### Checkpoint: Foundation — SELESAI ✅ (29/29 test hijau, shell terverifikasi di browser)

### Phase 2: Master Data & Pengaturan (Admin)
- [ ] Task 6: CRUD tahun ajaran (switch aktif), jurusan, mapel, guru
- [ ] Task 7: CRUD kelas + assign siswa (pivot) + generate/cetak QR kartu pelajar
- [ ] Task 8: Modul jadwal pelajaran + validasi bentrok guru/ruangan
- [ ] Task 9: Modul Pengaturan Hari (jam per hari + libur rutin) & Hari Libur Rentang

### Checkpoint: Master & Setting
- [ ] Admin bisa menyiapkan data sekolah penuh tanpa sentuh database

### Phase 3: Mesin Absensi
- [ ] Task 10: Service aturan waktu (hari aktif, gerbang, pulang) + unit test
- [ ] Task 11: Scanner gerbang masuk (kamera QR + endpoint scan + feedback)
- [ ] Task 12: Job otomatis: pre-create pulang, auto-alpa, auto-kabur + scheduler
- [ ] Task 13: Scanner pulang + pulang cepat (piket + karcis)
- [ ] Task 14: Absen mapel guru/wali kelas (pilih jadwal, set semua hadir)

### Checkpoint: Mesin Absensi
- [ ] Skenario E2E: scan masuk → alpa 08:00 → scan pulang → kabur terverifikasi (termasuk hari libur)

### Phase 4: Approval & Portal Orang Tua
- [ ] Task 15: Pengajuan izin/sakit ortu (upload foto) + approval wali kelas
- [ ] Task 16: Koreksi absensi dengan approval workflow (wali kelas → admin final)
- [ ] Task 17: Portal ortu (timeline 3 titik, rekap anak, ajukan izin, daftar notifikasi)

### Checkpoint: Approval
- [ ] Alur izin ortu → approve → status gerbang/mapel berubah otomatis; koreksi teraudit

### Phase 5: Laporan & Notifikasi
- [ ] Task 18: Notifikasi gateway simulasi + dispatcher queue + trigger event
- [ ] Task 19: Rekap harian/mingguan/bulanan + filter (admin/wali/bk)
- [ ] Task 20: Ekspor PDF & XLSX berkop sekolah
- [ ] Task 21: Dashboard admin KPI + laporan wali kelas & BK (siswa <90%)

### Checkpoint: Output
- [ ] Rekap & ekspor sesuai spesifikasi guru; notifikasi tercatat per event

### Phase 6: Finalisasi
- [ ] Task 22: Seeder demo data UJIKOM (siswa, jadwal, ortu, setting hari)
- [ ] Task 23: Verifikasi end-to-end + Pint + README panduan demo UJIKOM

### Checkpoint: Complete
- [ ] Semua acceptance criteria terpenuhi, siap presentasi Ujikom

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Timezone salah (UTC) → semua jam meleset | High | Task 1 set `APP_TIMEZONE=Asia/Jakarta` + test assertion waktu |
| Race condition approve izin vs cron alpa | High | Aturan UPDATE (bukan INSERT) di Task 14; unit test skenario |
| Job dobel jalan (scheduler menit-an) | Medium | Cache key idempoten per tanggal |
| Paket dompdf/excel/qr butuh internet composer | Medium | Task 1 gagal cepat → laporkan, fallback CSV bila perlu |
| Browser kamera tidak jalan di demo | Medium | Fallback input manual NISN/QR di halaman scan yang sama |
| Jadwal bentrok menimpa data | Medium | Validasi aplikasi Task 7 + unique index |

## Open Questions
- (none) — seluruh keputusan sudah diputuskan di review dan masuk DESAIN REVISI 3.

## Notes
- Detail per tugas ada di `tasks/todo.md` (acceptance criteria + verifikasi + file).
- Urutan mengikuti dependency graph: migrasi → model → auth → master/setting → rule engine →
  scanner/job → approval → laporan → finalisasi.
