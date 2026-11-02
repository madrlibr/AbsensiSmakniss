# RENCANA PEKERJAAN IMPLEMENTASI — SISTEM ABSENSI SISWA

> Dokumen roadmap implementasi tahap demi tahap. **FIXED** — urutan kerja terstruktur agar tidak loncat-loncat dan tidak error karena ketergantungan.

---

## Prinsip Penting

> **Tabel yang direferensikan oleh tabel lain harus dibuat lebih dulu.** Migrasi, model, seeder, controller, dan view dibuat dalam urutan yang sama untuk menghindari error foreign key dan dependencies.

---

## FASE 1: Fondasi & Lingkungan (Infrastructure) — ✅ SELESAI

| No | Tugas | Deskripsi | Alasan | Status |
|----|-------|-----------|--------|--------|
| 1.1 | Konfigurasi `.env` & database | MySQL, database `absensismaknisDB`, timezone Asia/Jakarta | Harus selesai dulu sebelum migration | ✅ Selesai |
| 1.2 | Tabel `users` dengan kolom `role` | Enum: `admin`, `guru`, `wali_kelas`, `bk`, `orang_tua`, `osis`, `piket` | Autentikasi membutuhkan role sebelum input data | ✅ Selesai |
| 1.3 | Laravel Breeze (Auth) | Blade + Tailwind, login/register/password reset | Sistem membutuhkan user login | ✅ Selesai |
| 1.4 | Tailwind CSS Tema Biru | `primary-500: #2563eb`, status colors, custom components | Tema sekolah | ✅ Selesai |
| 1.5 | `RoleMiddleware` | Alias `role` di `bootstrap/app.php` | Perlindungan route per role | ✅ Selesai |
| 1.6 | Build assets | `npm run build` berhasil | CSS/JS siap digunakan | ✅ Selesai |
| 1.7 | Verifikasi route | `php artisan route:list` semua route aktif | Pastikan auth berjalan | ✅ Selesai |

---

## FASE 2: Master Data Referensi (Tabel Induk)

Tabel-tabel ini **tidak bergantung pada tabel lain** (kecuali `users` yang sudah ada), sehingga dibuat paling awal.

| No | Tabel | Dependensi | Alasan Dibuat Dini |
|----|-------|------------|-------------------|
| 2.1 | `tahun_ajaran` | Tidak ada | Referensi untuk `kelas` dan `jadwal_pelajaran` |
| 2.2 | `jurusan` | Tidak ada | Referensi untuk `kelas` |
| 2.3 | `mapel` | Tidak ada | Referensi untuk `jadwal_pelajaran` |
| 2.4 | `guru` | `users`, `mapel` | Digunakan oleh `jadwal_pelajaran` |
| 2.5 | `kelas` | `tahun_ajaran`, `jurusan`, `users` | Digunakan oleh `siswa` dan `jadwal_pelajaran` |
| 2.6 | `siswa` | `kelas` | Digunakan oleh semua tabel absensi |
| 2.7 | `orang_tua` | Tidak ada | Digunakan oleh `siswa_orang_tua` dan `pengajuan_izin_sakit` |

**Output FASE 2:** Semua master data dapat diinput melalui seeder atau manual, siap direferensikan.

---

## FASE 3: Konfigurasi Sistem (Setting & Hari Libur)

| No | Tabel | Dependensi | Alasan |
|----|-------|------------|--------|
| 3.1 | `setting_absensi` | Tidak ada | Konfigurasi jam toleransi, batas tepat waktu, jam alpa, jendela pulang |
| 3.2 | `setting_sekolah` | Tidak ada | Konfigurasi hari libur rutin, nama sekolah, dll |
| 3.3 | `hari_libur` | `tahun_ajaran` | Cek apakah hari ini libur sebelum auto-alpa dijalankan |

**Output FASE 3:** Sistem mengetahui kapan libur, batas waktu absensi, dan jendela pulang.

---

## FASE 4: Tabel Absensi Inti (Inti Sistem)

Tabel ini bergantung pada master data dari FASE 2 & 3.

| No | Tabel | Dependensi | Alasan Urutan |
|----|-------|------------|---------------|
| 4.1 | `absensi_gerbang` | `siswa`, `users` | Absen pertama kali ketika siswa masuk sekolah |
| 4.2 | `absensi_mapel` | `siswa`, `jadwal_pelajaran`, `users` | Absen saat pelajaran di kelas |
| 4.3 | `absensi_pulang` | `siswa`, `users` | Absen ketika siswa pulang |

**Output FASE 4:** Tabel absensi siap menampung data scan/input.

---

## FASE 5: Pendukung Absensi (Izin, Koreksi, Notifikasi)

| No | Tabel | Dependensi | Alasan |
|----|-------|------------|--------|
| 5.1 | `siswa_orang_tua` | `siswa`, `orang_tua` | Pivot untuk notifikasi orang tua |
| 5.2 | `pengajuan_izin_sakit` | `siswa`, `orang_tua`, `users` | Orang tua mengajukan, wali kelas menyetujui |
| 5.3 | `koreksi_absensi` | `users` | Mengubah status absensi yang salah |
| 5.4 | `notifikasi_kehadiran` | `siswa`, `orang_tua`, `absensi_*` | Log notifikasi yang dikirim |

**Output FASE 5:** Alur izin orang tua, koreksi status, dan notifikasi dapat berjalan.

---

## FASE 6: Models & Relasi Eloquent

| No | Tugas | Alasan |
|----|-------|--------|
| 6.1 | Buat model untuk semua tabel (FASE 2 s.d. 5) | Model harus ada sebelum controller dan seeder |
| 6.2 | Definisikan relasi (belongsTo, hasMany, pivot) | Harus sesuai ERD yang disetujui |
| 6.3 | Tambahkan accessor/mutator (format tanggal, status badge) | Memudahkan tampilan di view |
| 6.4 | Tambahkan scope (scopeActive, scopeByTahunAjaran) | Memudahkan query di controller |

**Output FASE 6:** Semua model siap digunakan dengan relasi yang benar.

---

## FASE 7: Seeders (Data Awal)

| No | Seeder | Isi | Alasan |
|----|--------|-----|--------|
| 7.1 | `TahunAjaranSeeder` | Data tahun ajaran aktif (2026/2027) | Harus ada sebelum kelas dan jadwal |
| 7.2 | `JurusanSeeder` | Data jurusan SMK (TKJ, RPL, dll) | Harus ada sebelum kelas |
| 7.3 | `MapelSeeder` | Data mata pelajaran | Harus ada sebelum jadwal |
| 7.4 | `GuruSeeder` | Data guru & user terkait | Harus ada sebelum jadwal |
| 7.5 | `KelasSeeder` | Data kelas & wali kelas | Harus ada sebelum siswa |
| 7.6 | `SiswaSeeder` | Data siswa | Harus ada sebelum absensi |
| 7.7 | `OrangTuaSeeder` | Data orang tua | Harus ada sebelum siswa_orang_tua |
| 7.8 | `SettingSeeder` | Default setting absensi & sekolah | Harus ada sebelum logika absensi |
| 7.9 | `UserSeeder` | Akun admin, guru, wali kelas, BK, osis, piket, orang tua | Untuk login testing |

**Output FASE 7:** Database sudah berisi data dummy, siap untuk testing fitur.

---

## FASE 8: Routing & Controllers (per Modul)

Dibuat modul per modul, dari yang paling dasar hingga yang paling kompleks.

| No | Modul | Route | Controller | Alasan Urutan |
|----|-------|-------|------------|---------------|
| 8.1 | Dashboard | `/dashboard` | `DashboardController` | Halaman utama setelah login |
| 8.2 | Master Data | `/tahun-ajaran`, `/jurusan`, `/kelas`, `/mapel`, `/guru`, `/siswa`, `/orang-tua` | Resource controller masing-masing | CRUD master data harus ada sebelum input absensi |
| 8.3 | Absen Gerbang | `/absensi/gerbang` | `AbsenGerbangController` | Scan masuk, OSIS/Piket di gerbang |
| 8.4 | Absen Mapel | `/absensi/mapel` | `AbsenMapelController` | Guru absen di kelas |
| 8.5 | Absen Pulang | `/absensi/pulang` | `AbsenPulangController` | Scan pulang, OSIS/Piket di gerbang |
| 8.6 | Pulang Cepat | `/pulang-cepat` | `PulangCepatController` | Piket berikan izin pulang cepat + karcis |
| 8.7 | Pengajuan Izin/Sakit | `/pengajuan-izin` | `PengajuanIzinController` | Orang tua mengajukan, wali kelas menyetujui |
| 8.8 | Koreksi Absensi | `/koreksi-absensi` | `KoreksiAbsensiController` | Mengubah status absensi salah |
| 8.9 | Rekap & Laporan | `/rekap/harian`, `/mingguan`, `/bulanan` | `RekapController` | Output akhir sesuai spesifikasi guru |
| 8.10 | Setting & Hari Libur | `/setting`, `/hari-libur` | `SettingController`, `HariLiburController` | Konfigurasi sistem |

**Output FASE 8:** API/route dan logika backend untuk semua fitur siap.

---

## FASE 9: Frontend / Blade Views

| No | Halaman | Deskripsi |
|----|---------|-----------|
| 9.1 | Layout utama | Sidebar, navbar, template dasar |
| 9.2 | Dashboard | Ringkasan absensi hari ini, notifikasi pending |
| 9.3 | CRUD Master Data | Form input tabel master |
| 9.4 | Scan Absen Gerbang | Halaman scan untuk OSIS/Piket (mobile-first) |
| 9.5 | Absen Mapel | Tabel daftar siswa + dropdown status per siswa |
| 9.6 | Absen Pulang | Halaman scan pulang (mobile-first) |
| 9.7 | Pulang Cepat | Form piket + karcis |
| 9.8 | Pengajuan Izin/Sakit (Orang Tua) | Form upload foto surat, riwayat pengajuan |
| 9.9 | Approval Izin (Wali Kelas) | Daftar pengajuan pending + tombol setujui/tolak |
| 9.10 | Rekapitulasi | Tabel rekap harian/mingguan/bulanan + export PDF/Excel |
| 9.11 | Setting & Hari Libur | Form konfigurasi |

**Output FASE 9:** Antarmuka pengguna lengkap untuk semua role.

---

## FASE 10: Notifikasi & Scheduled Jobs (Otomatisasi)

| No | Tugas | Deskripsi | Alasan |
|----|-------|-----------|--------|
| 10.1 | Notification Class | Buat class notifikasi WhatsApp/SMS/Email ke orang tua | Dibutuhkan setelah absensi tersimpan |
| 10.2 | Scheduled: Auto-Alpa | Command berjalan setiap hari pada `jam_alpa` | Harus dibuat setelah `absensi_gerbang` & `setting_absensi` siap |
| 10.3 | Scheduled: Auto-Kabur | Command menandai siswa belum scan pulang → `kabur` | Harus dibuat setelah `absensi_pulang` siap |
| 10.4 | Mail/Notification Setup | Konfigurasi mailtrap atau driver notifikasi | Untuk pengujian notifikasi |

**Output FASE 10:** Sistem otomatis menandai alpa & kabur, dan mengirim notifikasi ke orang tua.

---

## FASE 11: Testing & QA

| No | Tugas | Deskripsi |
|----|-------|-----------|
| 11.1 | Unit Test (Model) | Test relasi dan scope model |
| 11.2 | Feature Test (Controller) | Test route absensi, izin, rekap |
| 11.3 | Uji Coba Manual | Login sebagai setiap role, coba semua alur |
| 11.4 | Uji Edge-Case | Pulang cepat, kartu lupa, guru tidak masuk, hari libur, auto-alpa di hari libur |

**Output FASE 11:** Sistem siap digunakan.

---

## 📊 Ringkasan Urutan Implementasi

```
FASE 1: Fondasi (Env, Auth, Middleware, Tailwind)        ✅ SELESAI
    ↓
FASE 2: Master Data (Tahun Ajaran → Jurusan → Mapel → Guru → Kelas → Siswa → Orang Tua)
    ↓
FASE 3: Konfigurasi (Setting Absensi, Setting Sekolah, Hari Libur)
    ↓
FASE 4: Tabel Absensi Inti (Gerbang → Mapel → Pulang)
    ↓
FASE 5: Pendukung (SiswaOrangTua, PengajuanIzin, Koreksi, Notifikasi)
    ↓
FASE 6: Models & Relasi Eloquent
    ↓
FASE 7: Seeders (Data Awal)
    ↓
FASE 8: Routing & Controllers (per Modul)
    ↓
FASE 9: Frontend / Blade Views
    ↓
FASE 10: Notifikasi & Scheduled Jobs (Auto-Alpa, Auto-Kabur)
    ↓
FASE 11: Testing & QA
```

---

## ✅ Checklist Siap Implementasi

Sebelum memulai FASE 2, pastikan:
- [x] FASE 1 selesai (Auth, RoleMiddleware, Tailwind tema biru, role piket ditambahkan)
- [x] `DESAIN_SISTEM_ABSENSI.md` — Database design & ERD sudah FIXED
- [x] `DESAIN_UI_UX.md` — UI/UX design sudah FIXED
- [x] `RENCANA_PEKERJAAN.md` — Implementation plan sudah FIXED

---

**Status:** Siap melanjutkan ke **FASE 2** (Master Data Referensi).

Dibuat untuk UJIKOM — Sistem Absensi Siswa (Laravel, SMK).