# Task List — Sistem Absensi Siswa SMK (UJIKOM)

> Rincian dari `tasks/plan.md`. Status: `[ ]` belum, `[x]` selesai.
> Desain acuan: `Planner/DESAIN_SISTEM_ABSENSI.md` (REVISI 3).
> UI/UX acuan: `Planner/PrdAndDesign/*/code.html` + `smk_attendance_os/DESIGN.md`.

## Phase 1: Fondasi

## Task 1: Environment MySQL, timezone, scheduler, paket

**Description:** Menyiapkan `.env` ke MySQL (DB `absensi_smk`), timezone Asia/Jakarta, scheduler Laravel, dan menginstal paket pendukung (dompdf, laravel-excel, endroid/qr-code, laravel/boost) plus dependensi npm scan QR.

**Acceptance criteria:**
- [x] `.env` memakai `DB_CONNECTION=mysql`, `APP_TIMEZONE=Asia/Jakarta`, kredensial DB valid
- [x] `php artisan migrate:fresh` sukses di MySQL; `config('app.timezone')` = Asia/Jakarta (diperbaiki: skeleton hardcode UTC → `env('APP_TIMEZONE')`)
- [x] Paket composer `dompdf/dompdf`, `openspout/openspout`, `endroid/qr-code`, `laravel/boost` (dev) terpasang; `html5-qrcode` masuk `package.json`. Entry scheduler `absensi:proses-otomatis` ditambahkan pada Task 12 (saat command-nya dibuat)

**Verification:**
- [x] Test: `php artisan test` hijau (2/2)
- [x] Manual: `migrate:fresh` sukses di MySQL · `schedule:list` jalan · `now()` = WIB

**Dependencies:** None
**Files likely touched:** `.env`, `package.json`, `routes/console.php`, `composer.json`
**Estimated scope:** S

## Task 2: Migrasi & model master (users/role, tahun_ajaran, jurusan, mapel, guru)

**Description:** Membuat migrasi + model + relasi untuk tabel master awal sesuai desain §6.1–6.5, termasuk enum `role` user dan penambahan `is_active`.

**Acceptance criteria:**
- [x] Tabel `tahun_ajaran`, `jurusan`, `mapel`, `guru` termigrasi sesuai spesifikasi kolom & index unik (+ kolom `role`/`is_active` di users)
- [x] Model punya relasi (guru→users, guru→mapel, dsb.), soft delete, cast enum `Role`
- [x] Seeder dasar: 1 admin (`admin@sekolah.test` / `password`) + TA 2026/2027 aktif

**Verification:**
- [x] Test: `tests/Feature/MasterDataTest.php` (scope TA, cast role, relasi guru)
- [x] Manual: `migrate:fresh --seed` sukses di MySQL

**Dependencies:** Task 1
**Files likely touched:** `database/migrations/*`, `app/Models/{User,TahunAjaran,Jurusan,Mapel,Guru}.php`, `app/Enums/Role.php`, `database/seeders/DatabaseSeeder.php`
**Estimated scope:** M

## Task 3: Migrasi & model siswa/kelas (siswa, siswa_kelas, orang_tua, siswa_orang_tua)

**Description:** Membuat tabel siswa (dengan `qr_code` unik, tanpa `kelas_id`), kelas (per tahun ajaran), pivot `siswa_kelas`, orang tua (`user_id`) dan pivot `siswa_orang_tua` sesuai desain §6.6–6.10.

**Acceptance criteria:**
- [x] Index unik: `siswa.qr_code`, `siswa.nisn`, `siswa.nis`, `siswa_kelas(siswa_id, tahun_ajaran_id)` — semua ada
- [x] Relasi Eloquent lengkap & timbal balik (siswa↔kelas via pivot, siswa↔orang tua, cast pivot via `->using()`)
- [x] `qr_code` auto-generate saat siswa dibuat (`SISWA-{id}-{nisn}`, via hook `created`)

**Verification:**
- [x] Test: `MasterDataTest` — auto QR, lookup QR/NISN/NIS, riwayat kelas lintas TA, ikatan ortu
- [x] Manual: `migrate:fresh --seed` sukses

**Dependencies:** Task 2
**Files likely touched:** `database/migrations/*`, `app/Models/{Siswa,Kelas,SiswaKelas,OrangTua,SiswaOrangTua}.php`
**Estimated scope:** M

## Task 4: Fondasi UI/UX — design tokens, shell layout, komponen Blade

**Description:** Menerjemahkan design system mockup (`PrdAndDesign/*/code.html` + `DESIGN.md`) ke aplikasi: (a) seluruh token Tailwind v4 di `resources/css/app.css` (`@theme`: warna M3, `--text-*` skala tipografi, `--font-*` Plus Jakarta Sans/Inter, `--spacing-space-*`, radius) **dengan nama class identik dengan mockup**, (b) `layouts/app.blade.php` (sidebar `w-72` + topbar + area konten, responsif mobile: off-canvas), (c) komponen Blade `x-ui/*` hasil port markup mockup, (d) login page bergaya sama.

**Acceptance criteria:**
- [x] Seluruh token mockup didefinisikan di `@theme` Tailwind v4 — class identik (`bg-surface-container-lowest`, `text-headline-lg`, `p-space-md`, `font-title-md`, radius & shadow DESIGN.md) — diverifikasi via galeri `/ui` di browser
- [x] Shell tampil sesuai mockup: sidebar bertingkat (per role via `App\Support\Menu`), topbar (chip sekolah, tahun ajaran, jam WIB live, profil), off-canvas mobile (`#sidebar` + backdrop + tombol hamburger)
- [x] Komponen `x-ui/*` tersedia & terpakai di galeri: btn, panel, kpi-card, status-badge (peta warna 9 status), badge, avatar, page-header, data-table/th/td, filter-bar, pagination, empty-state, input, select, textarea, timeline-item
- [x] Halaman login (`x-layouts.guest`) bergaya sama; focus-ring a11y di CSS base; aria-label pada icon-button

**Verification:**
- [x] Test: `npm run build` sukses; `UiTest` 6 test hijau (login, galeri, menu per role)
- [x] Manual di browser: `/ui` seluruh varian sesuai token mockup; `/login` tampil benar; ketemu & perbaiki 3 bug (path `components/layouts`, komentar CSS berisi `*/` yang membatalkan `@theme`, campur `@php()` inline + blok `@endphp` di satu view)

**Dependencies:** Task 1
**Files likely touched:** `resources/css/app.css`, `resources/views/components/layouts/app.blade.php`, `resources/views/components/ui/*.blade.php`, `resources/views/auth/login.blade.php`, `resources/js/app.js`, `vite.config.js`, `app/Support/Menu.php`, `app/Enums/StatusAbsensi.php`
**Estimated scope:** L

## Task 5: Auth login + guard role + menu per role

**Description:** Halaman login/logout manual (session + throttle), middleware `role:` (admin, guru, wali_kelas, bk, orang_tua, osis, piket) di `bootstrap/app.php`, proteksi rute per matriks desain §4 — memakai shell dari Task 4. (Gate per aksi approval menyusul di Task 15–16 bersama workflow-nya.)

**Acceptance criteria:**
- [x] Login valid → dialihkan sesuai role (ortu → portal, sisanya → dashboard); role lain ditolak 403
- [x] Rute dilindungi middleware `auth` + `role:`; password di-hash; `is_active=false` ditolak; login di-throttle (10/menit IP+email)
- [x] Menu sidebar menyesuaikan role (`App\Support\Menu`)

**Verification:**
- [x] Test: `AuthTest` 13 test — login 7 role, password salah, user nonaktif, logout, tamu redirect, 403 lintas role
- [x] Manual: login `admin@sekolah.test` di browser → shell tampil dengan 4 grup menu + topbar profil/logout

**Dependencies:** Task 2, Task 4
**Files likely touched:** `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Middleware/EnsureUserHasRole.php`, `bootstrap/app.php`, `routes/web.php`, `resources/views/auth/login.blade.php`
**Estimated scope:** M

### Checkpoint: Foundation — SELESAI ✅
- [x] `php artisan migrate:fresh --seed` sukses di MySQL · shell & komponen sesuai mockup (verifikasi browser) · login 7 role berfungsi · `php artisan test` hijau (29/29) · **review manusia sebelum Phase 2**

## Phase 2: Master Data & Pengaturan (Admin)

## Task 6: CRUD tahun ajaran (switch aktif), jurusan, mapel, guru

**Description:** Modul admin: index/create/edit/delete (soft delete) untuk tahun ajaran, jurusan, mapel, guru; form guru menautkan `users` + `mapel_utama`; aksi "Jadikan Aktif" (hanya satu `is_active`).

**Acceptance criteria:**
- [x] CRUD berfungsi penuh + validasi (unik singkatan/nip/kode_mapel)
- [x] Switch tahun ajaran aktif menonaktifkan TA lain dalam satu transaksi
- [x] Hanya role admin yang bisa mengakses

**Verification:**
- [x] Test: feature CRUD + constraint satu TA aktif (`MasterCrudTest`, 14 test)
- [x] Manual: buat 2 TA via form, "Jadikan Aktif" bergantian via UI (dialog konfirmasi) — hanya satu TA aktif

**Dependencies:** Task 5
**Files likely touched:** `app/Http/Controllers/{TahunAjaran,Jurusan,Mapel,Guru}Controller.php`, `resources/views/master/*`
**Estimated scope:** M
**Catatan:** Fondasi Task 8/9 dibuat awal di task ini: enum `Hari`/`Semester`, migrasi & model `jadwal_pelajaran`, `pengaturan_hari`, `hari_libur` (agar relasi `Mapel::jadwalPelajaran` aman). Keunikan slot jadwal divalidasi di aplikasi (tanpa index unik DB) agar baris soft-delete tidak menghalangi pembuatan ulang slot.

## Task 7: CRUD kelas + assign siswa (pivot) + generate/cetak QR kartu pelajar

**Description:** Admin membuat kelas per TA, menugaskan wali kelas, memindahkan siswa antar kelas via `siswa_kelas`, CRUD siswa (tampilan mengikuti mockup `master_data_siswa`), dan mencetak lembar QR kartu pelajar.

**Acceptance criteria:**
- [x] Kelas hanya tampil pada TA-nya; assign siswa memakai pivot (bukan kolom langsung)
- [x] Siswa terdaftar → `qr_code` ter-generate & halaman cetak kartu menampilkan QR + NIS/nama/kelas
- [x] Perpindahan siswa antar kelas tercatat riwayatnya (TA lama tidak berubah)

**Verification:**
- [x] Test: assign pivot, pindah kelas, QR unique (`SiswaKelasTest`, 15 test)
- [x] Manual: buat kelas & siswa via form, tambah/pindah/keluarkan anggota via UI (dialog konfirmasi), cetak kartu PDF — 2 QR terrender bersih & valid `%PDF` (diperiksa di browser PDF viewer)

**Dependencies:** Task 3, Task 6
**Files likely touched:** `app/Http/Controllers/{KelasController,SiswaController,KartuPelajarController}.php`, `resources/views/master/{kelas,siswa}/*`
**Estimated scope:** L
**Catatan:** Cetak kartu memakai dompdf + endroid/qr-code v6 (constructor injection — `QrCode::create()` tidak ada di v6); layout kartu CSS tabel murni (dompdf tak mendukung flexbox). Bug yang ditemukan & diperbaiki: `:options` Blade dengan double-quote bersarang membuat select Tambah Anggota render source mentah — sudah ditambah assertion regresi di test. Menu "Data Jurusan" ditambahkan agar CRUD jurusan (Task 6) reachable; label "Data Kelas & Jurusan" dipisah jadi "Data Jurusan" + "Data Kelas".

## Task 8: Modul jadwal pelajaran + validasi bentrok guru/ruangan

**Description:** CRUD jadwal (kelas, mapel, guru, hari, jam_ke, jam mulai/selesai, ruangan, semester) dengan validasi: unik per kelas+jam_ke, **guru & ruangan tidak bentrok** pada hari/jam/semester yang sama.

**Acceptance criteria:**
- [x] Bentrok kelas → validasi ditolak pesan jelas; bentrok guru/ruangan → ditolak
- [x] Filter per kelas & semester; hanya jadwal TA aktif yang tampil untuk input absen
- [x] Hanya admin yang mengelola (wali kelas read-only)

**Verification:**
- [x] Test: 3 skenario bentrok (kelas, guru, ruangan) + tumpang tindih jam_ke beda & semester beda boleh (`JadwalPelajaranTest`, 12 test)
- [x] Manual: buat jadwal 2 kelas dengan guru sama → ditolak "Bentrok guru: Pak Ahmad sudah mengajar Matematika untuk X TKJ 1 jam ke-1 pada Senin." (via UI & fetch; baris kedua tidak masuk DB)

**Dependencies:** Task 6
**Files likely touched:** `app/Http/Controllers/JadwalPelajaranController.php`, `app/Services/Jadwal/ValidasiBentrok.php`, `resources/views/master/jadwal/*`
**Estimated scope:** M
**Catatan:** Rute `jadwal.index` dibuka untuk `role:admin,wali_kelas` (wali kelas baca-saja; tombol aksi disembunyikan via pengecekan role di view). Waktu dinormalisasi ke `H:i:s` di service agar perbandingan aman di MySQL (TIME) & SQLite (teks).

## Task 9: Modul Pengaturan Hari (jam per hari + libur rutin) & Hari Libur Rentang

**Description:** Halaman admin mengikuti mockup `pengaturan_jam_absensi`: 7 baris `pengaturan_hari` (semua jam dikustom per hari + toggle masuk/libur = modul hari libur rutin, gaya tombol hari SEN–MIN) dan CRUD `hari_libur` rentang tanggal dengan `status_masuk` (tabel kalender libur).

**Acceptance criteria:**
- [x] Admin bisa mengubah seluruh jam per hari; menyimpan valid (akhir > mulai, dll.)
- [x] Toggle `status_hari` = libur tersimpan & memblokir hari tersebut di rule engine
- [x] CRUD hari libur rentang (multi-hari & satu hari) berfungsi; seed default Senin–Jumat masuk

**Verification:**
- [x] Test: simpan setting hari, urutan waktu ditolak, reset default, CRUD libur rentang & validasi tanggal (`PengaturanHariTest`, 11 test)
- [x] Manual: ubah Jumat jadi pendek (alpa 07:30, pulang 11:30–12:45) via form 7 hari — tersimpan, hari lain tak ikut berubah; uji batas < buka → ditolak "…harus berurutan…", nilai tak tersimpan; buat "Libur Akhir Tahun" rentang + "UKK" satu hari (badge Libur/Kegiatan benar)

**Dependencies:** Task 1, Task 5
**Files likely touched:** `app/Http/Controllers/{PengaturanHariController,HariLiburController}.php`, `resources/views/pengaturan/*`
**Estimated scope:** M
**Catatan:** Halaman jam = 7 kartu (segmented Masuk/Libur ala tombol SEN–MIN mockup; kartu menggelap saat libur via `data-segmen` + app.js) + KPI ringkasan dari hari masuk pertama + Reset Default. Validasi urutan: buka ≤ batas ≤ tutup ≤ auto-alpa & mulai < akhir pulang (pesan `gagal` per hari). `FIELD()` MySQL diganti sort PHP agar lintas-DB (SQLite saat test). Seeder `PengaturanHariSeeder` idempoten di-hook ke `DatabaseSeeder`. Bug tertangkap test/regresi: view form libur sempat hilang (tertulis saat limit hit) → ditambah assertion render form create & edit.

### Checkpoint: Master & Setting
- [x] Admin bisa menyiapkan data sekolah lengkap tanpa menyentuh database · test hijau (81 test / 338 assertion; Tasks 6–9 mencakup TA, jurusan, mapel, guru, kelas, siswa+QR, jadwal anti-bentrok, jam per hari, libur rentang)

## Phase 3: Mesin Absensi

## Task 10: Service aturan waktu (hari aktif, gerbang, pulang) + unit test

**Description:** `HariAktifRules::status(Tanggal)` (precedence desain §7.1), `GerbangRules::tentukanStatus(waktu)` dan `PulangRules::dalamJendela(waktu)` berbasis `pengaturan_hari`. Murni service + test, belum controller.

**Acceptance criteria:**
- [x] Precedence: libur rentang tidak_masuk > hari_libur masuk (paksa aktif) > status_hari libur > aktif
- [x] Gerbang: sebelum buka / tepat waktu / terlambat / lewat tutup / jam NULL→diblokir — semua kasus teruji
- [x] Pulang: sebelum jendela / dalam jendela / lewat jendela teruji

**Verification:**
- [x] Test: unit 27 kasus lintas3 service (`HariAktifRulesTest` 10, `GerbangRulesTest` 9, `PulangRulesTest` 8; termasuk jam NULL, hari libur, satu hari vs rentang, precedence tumpang tindih)
- [x] Manual: tinker — 06:30 "Gerbang belum dibuka — buka pukul 06:45." · 07:20 terlambat · 08:00 piket terlambat+keteranganWajib · Sabtu libur rutin · 13:00 pulang diblokir "jendela 14:00–15:30" · 15:00 pulang · 11 Jan 2027 (UKK kegiatan) Hari Aktif (Kegiatan) · 25 Des (rentang tidak_masuk) Libur Mutlak

**Dependencies:** Task 9
**Files likely touched:** `app/Services/Absensi/{HariAktifRules,GerbangRules,PulangRules,HasilHariAktif,HasilAturan}.php`, `app/Enums/StatusHari.php`, `tests/Unit/*`
**Estimated scope:** M
**Catatan:** `Hari::dariTanggal()` (index `date('w')`, tanpa locale). Rentang libur: multi-hari `tanggal_selesai >= T` ATAU satu hari (`selesai NULL` + `tanggal_mulai = T`) — cegah baris satu hari bocor ke tanggal setelahnya. `GerbangRules::tentukanStatus($waktu, inputPetugas: true)` = jalan pintas petugas lewat jam tutup (Terlambat + `keteranganWajib`). Batas waktu inclusive (`<= batas` = tepat waktu; `<= tutup` = terlambat; `<= akhir` = pulang).

## Task 11: Scanner gerbang masuk (kamera QR + endpoint scan)

**Description:** Halaman scan untuk osis/piket mengikuti mockup `scanner_gerbang` (viewfinder gelap + sudut bidik, kartu hasil scan hijau/kuning/merah, statistik tepat/terlambat/dispensasi, log scan terkini, aksi petugas piket): kamera `html5-qrcode` + input manual NISN, POST endpoint scan memanggil `GerbangRules`, idempoten, feedback suara/getar.

**Acceptance criteria:**
- [x] Scan QR valid pada jam buka → `hadir_tepat_waktu`/`terlambat` sesuai setting hari itu
- [x] Scan ganda → info "sudah absen HH:MM" tanpa duplikat; setelah jam tutup QR ditolak
- [x] Input manual petugas piket setelah tutup → `terlambat` + keterangan wajib
- [x] Hari libur → scan ditolak dengan pesan libur

**Verification:**
- [x] Test: feature endpoint scan 17 kasus (`ScanGerbangTest`: QR pagi/terlambat, manual NISN, idempoten, alpa/izin block, tutup+petugas/keterangan, OSIS tanpa jalan manual, libur rutin & mutlak, siswa nonaktif, kode ngawur, halaman/role, statistik+log)
- [x] Manual: halaman scan di browser — hari Minggu → banner "Scan diblokir — Hari ini libur sesuai pengaturan sekolah." + kamera nonaktif; dipaksa masuk sementara → Input Manual NISN siswa demo → kartu hijau "HADIR TEPAT WAKTU" 15:02:53 WIB + KPI 1/0/1 + log row "X TKJ 2 · 15:02"; scan ulang → pill kuning "SUDAH ABSEN JAM 15:02" tanpa duplikat/KPI naik; kamera di harness → fallback "Kamera tidak tersedia — pakai Input Manual"; state Minggu dikembalikan ke libur

**Dependencies:** Task 7, Task 10
**Files likely touched:** `app/Http/Controllers/ScanGerbangController.php`, `routes/web.php`, `resources/views/scan/gerbang.blade.php`, `resources/js/scan.js`
**Estimated scope:** L
**Catatan:** Tabel `absensi_gerbang` (unique siswa+tanggal) + model; `ScanGerbangService` resolve via `Siswa::cariIdentitas()` (qr_code/nisn/nis), race dua scan → QueryException ditangkap jadi info idempoten; `petugasBolehManual` = admin/piket saja (OSIS tolak setelah tutup); endpoint JSON `scan.gerbang.proses` (422 utk tolak); view port mockup mobile ke shell desktop (mode Pulang & aksi Toleransi/Karcis disabled → Task 13/14); `scan.js` entry vite terpisah (html5-qrcode, cooldown jeda 3 dtk + pause/resume, beep WebAudio + vibrate, countdown toleransi→tutup dengan offset jam server, render kartu via textContent anti-XSS).

## Task 12: Job otomatis: pre-create pulang, auto-alpa, auto-kabur + scheduler

**Description:** Command `absensi:proses-otomatis` berjalan tiap menit; memeriksa `pengaturan_hari` hari ini + idempoten via cache key; menjalankan pre-create `absensi_pulang` (`belum_absen`), auto-alpa (anti-join gerbang), auto-kabur (update `belum_absen`→`kabur` hanya yang hadir pagi), melewati hari libur & siswa nonaktif, dan memicu notifikasi.

**Acceptance criteria:**
- [x] Pada jam alpa: siswa tanpa gerbang → `alpa`; siswa ber-`izin`/`sakit` tidak tersentuh
- [x] Pada akhir jendela pulang: hanya siswa hadir pagi tanpa scan → `kabur`
- [x] Job dobel dalam sehari tidak menggandakan data (idempoten); hari libur = nol aksi
- [x] Terdaftar di scheduler (`routes/console.php`)

**Verification:**
- [x] Test: feature job 17 kasus (`ProsesOtomatisTest` — pre-create 5: populasi/nonaktif/bukan anggota/idempoten/sebelum buka; alpa 4: anti-join/izin-sakit skip/idempoten/sebelum jam; kabur 4: hanya hadir pagi/skip sudah pulang-sakit/idempoten/sebelum akhir; libur nol aksi, jam NULL nol aksi, siklus penuh 2× identik)
- [x] Manual: `php artisan absensi:proses-otomatis` 2× berturut di MySQL — Minggu libur → "Dilewati: Hari ini libur sesuai pengaturan sekolah."; dipaksa masuk (jam alpa 12:00 & tutup pulang 14:00 sudah lewat, 1 siswa hadir pagi) → run1 "Pre-create pulang: 2 · Auto-alpa: 1 · Auto-kabur: 1" → run2 "0 · 0 · 0"; DB: Rizky (hadir 06:50) pulang=**kabur**, Anindya (alpa) pulang=**belum_absen** dibiarkan, gerbang Anindya=alpa metode auto waktu NULL; `schedule:list` → `* * * * * php artisan absensi:proses-otomatis`; state demo dikembalikan

**Dependencies:** Task 10, Task 11
**Files likely touched:** `app/Console/Commands/ProsesOtomatisAbsensi.php`, `app/Services/Absensi/{AutoAlpa,AutoKabur}.php`, `routes/console.php`
**Estimated scope:** M
**Catatan:** Tabel `absensi_pulang` pre-created (unique siswa+tanggal, `kode_karcis` unique utk Task 13) + model; populasi target = scope `Siswa::diTaAktif()` (kolom active + pivot is_active di TA aktif). Service murni `PrecreatePulang`/`AutoAlpa`/`AutoKabur::jalankan(tanggal)`; command yang time-check (buka → pre-create; jam_auto_alpa → alpa; akhir pulang +1 mnt → kabur) + `Cache::add("absensi:{precreate|alpa|kabur}:{tanggal}", ttl 26 jam)`; hari libur/jam belum dikonfigurasi = skip tanpa query. Baris job bermetode `auto` (traceable). Race scan-vs-alpa ditangkap `QueryException` → siswa menang. Notifikasi dispatcher = Task 14.

## Task 13: Scanner pulang + pulang cepat (piket + karcis)

**Description:** Halaman scan pulang (tab kedua dari mockup scanner, jendela per hari), endpoint update `absensi_pulang` → `pulang`; aksi piket `pulang_cepat` dengan keterangan wajib + `kode_karcis` (bisa dicetak).

**Acceptance criteria:**
- [x] Scan dalam jendela → `pulang` + waktu tersimpan; di luar jendela ditolak dengan pesan
- [x] Piket set `pulang_cepat` → kode karcis unik tergenerate, bisa dicetak, status tersimpan
- [x] Scan ganda idempoten; hari libur ditolak

**Verification:**
- [x] Test: feature endpoint pulang + aksi karcis
- [x] Manual: scan dalam & luar jendela, cetak karcis

**Dependencies:** Task 12
**Files likely touched:** `app/Http/Controllers/ScanPulangController.php`, `resources/views/scan/{pulang,karcis}.blade.php`
**Estimated scope:** M
**Catatan:** `ScanPulangService` — cek hari aktif dulu (pesan libur konsisten semua jalur), baris wajib pre-created (Task 12), final (`pulang`/`pulang_cepat`) = idempoten info, `kabur` = tolak "koreksi". `pulang_cepat` piket/admin kapan pun + keterangan wajib → `kode_karcis` deterministik `KC-{YYMMDD}-{NISN}` (unik: nisn unik + baris unik siswa+tanggal) — re-print kode sama. `HasilScan::$baris` di-relax ke union `AbsensiGerbang|AbsensiPulang`. `Role::bolehInputManual()` (admin+piket) dipakai lintas gerbang/pulang; service sendiri yang menolak non-petugas (controller tidak mematikan flag agar pesan jelas). Karcis = halaman printable `guest` layout + `@media print .no-print`. scan.js generalized `data-mode="gerbang|pulang"`: countdown batas/tutup vs mulai/akhir, KPI map per mode (pulang: Sudah Pulang/Pulang Cepat/Belum Absen), form karcis + tombol "Cetak" di kartu hasil. Menu pisah "Scanner Gerbang"/"Scanner Pulang". 14 test (suite 155/155).

## Task 14: Absen mapel guru/wali kelas (pilih jadwal, set semua hadir)

**Description:** Halaman mengikuti mockup `presensi_mata_pelajaran`: kartu info jadwal (mapel, guru, jam, ruang), KPI kelas, tombol "Tandai Semua Hadir", checklist/status per siswa dengan sinkron tap gerbang, simpan batch upsert.

**Acceptance criteria:**
- [ ] Hanya jadwal hari ini yang diampu guru yang tampil; guru lain tidak bisa akses kelasnya
- [ ] Simpan batch idempoten (ubah status berkali-kali tidak dobel)
- [ ] Status `izin/sakit/alpa` memicu entri notifikasi (Task 18 — antre `antre` dulu)

**Verification:**
- [ ] Test: feature akses & upsert batch
- [ ] Manual: set semua hadir → ubah 2 siswa → simpan ulang

**Dependencies:** Task 8, Task 10
**Files likely touched:** `app/Http/Controllers/AbsensiMapelController.php`, `resources/views/absensi/mapel/*`
**Estimated scope:** L

### Checkpoint: Mesin Absensi
- [ ] Skenario E2E: scan masuk → auto-alpa → scan pulang → auto-kabur terverifikasi, termasuk hari libur · **review manusia**

## Phase 4: Approval & Portal Orang Tua

## Task 15: Pengajuan izin/sakit ortu (upload foto) + approval wali kelas

**Description:** Form ortu (jenis, rentang tanggal, alasan, upload foto bukti) → `pending`; halaman approve wali kelas (gaya panel mockup dashboard: kartu permintaan + tombol Setujui/Tolak) dengan override admin; **efek persetujuan**: upsert `absensi_gerbang` (UPDATE `alpa`→`izin/sakit` bila ada) + `absensi_mapel` semua jadwal hari itu, tiap hari aktif dalam rentang.

**Acceptance criteria:**
- [ ] Ortu hanya bisa mengajukan untuk anaknya; upload tervalidasi (tipe/ukuran) & tersimpan publik
- [ ] Approve mengisi gerbang & mapel untuk setiap hari aktif dalam rentang; baris `alpa` lama **di-update**, bukan duplikat (anti race-cron)
- [ ] Tolak wajib beri catatan; wali kelas hanya melihat siswa kelasnya; notifikasi tercatat

**Verification:**
- [ ] Test: skenario approve sebelum & sesudah job alpa (2 kasus utama) + rentang lintas hari libur
- [ ] Manual: alur ortu→wali kelas penuh

**Dependencies:** Task 12, Task 17
**Files likely touched:** `app/Http/Controllers/{PengajuanIzin,ReviewIzin}Controller.php`, `app/Services/Absensi/EfekPersetujuanIzin.php`, `resources/views/{portal/wali,review}/*`
**Estimated scope:** L

## Task 16: Koreksi absensi dengan approval workflow (wali kelas → admin final)

**Description:** Form koreksi (jenis absen → pilih baris → status baru + alasan + foto), antrean review per matriks §4 (guru/osis/piket/bk → wali kelas/admin; wali kelas → admin; admin langsung +audit), saat `disetujui` update baris absensi & notifikasi.

**Acceptance criteria:**
- [ ] Hanya baris absensi nyata yang bisa dikoreksi; `status_lama` diambil otomatis dari baris
- [ ] Approval sesuai matriks role; disetujui → baris absensi benar-benar berubah + tercatat `approved_by`
- [ ] Ditolak butuh catatan; semua transisi tercatat di tabel koreksi (tanpa hapus data)

**Verification:**
- [ ] Test: matriks role (4+ kasus) + efek update absensi
- [ ] Manual: koreksi gerbang dari piket → approve wali kelas

**Dependencies:** Task 11, Task 14
**Files likely touched:** `app/Http/Controllers/KoreksiAbsensiController.php`, `resources/views/koreksi/*`
**Estimated scope:** M

## Task 17: Portal ortu (timeline 3 titik, rekap anak, ajukan izin, daftar notifikasi)

**Description:** Dashboard ortu mengikuti mockup `portal_orang_tua`: kartu anak (foto/NISN/kelas), banner status "Hadir di sekolah", **Perjalanan Hari Ini** (timeline 3 titik: gerbang masuk → mapel → pulang), Layanan Cepat (ajukan izin, riwayat), Rekap bulanan dengan ambang 90%, Pengajuan terakhir, panel BK; pindah anak bila multi-anak.

**Acceptance criteria:**
- [ ] Ortu hanya melihat anaknya sendiri (dicek lewat `siswa_orang_tua`)
- [ ] Timeline menampilkan 3 titik dengan waktu/status terkini dari ketiga tabel absensi
- [ ] Pengajuan izin dari portal (Task 15) & daftar notifikasi berfungsi

**Verification:**
- [ ] Test: isolasi data antar ortu
- [ ] Manual: ortu dengan 2 anak bisa berganti anak

**Dependencies:** Task 5, Task 11
**Files likely touched:** `app/Http/Controllers/PortalOrangTuaController.php`, `resources/views/portal/*`
**Estimated scope:** L

### Checkpoint: Approval
- [ ] Alur izin ortu → approve → status gerbang/mapel berubah otomatis; koreksi teraudit · **review manusia**

## Phase 5: Laporan & Notifikasi

## Task 18: Notifikasi gateway simulasi + dispatcher queue + trigger event

**Description:** Interface `NotifikasiGateway` + `DatabaseGateway` (simulasi), `KirimNotifikasiJob` di queue database, pemanggil dari semua trigger (scan masuk/pulang, karcis, auto-alpa, auto-kabur, approve izin, koreksi, absen mapel izin/sakit/alpa).

**Acceptance criteria:**
- [ ] Setiap trigger menghasilkan baris `notifikasi_kehadiran` status `antre`→`terkirim` (simulasi) dengan pesan ter-render
- [ ] Tidak ada notifikasi ganda untuk event yang sama dalam satu hari (dedup per event+baris)
- [ ] `php artisan queue:work` memproses antrean; ganti gateway cukup lewat binding container

**Verification:**
- [ ] Test: event → baris notifikasi (minimal 5 trigger)
- [ ] Manual: jalankan queue, lihat status berubah & tampil di portal ortu

**Dependencies:** Task 11, Task 12, Task 14
**Files likely touched:** `app/Services/Notifikasi/*`, `app/Jobs/KirimNotifikasiJob.php`, `app/Services/Absensi/NotifikasiTriggers.php`
**Estimated scope:** M

## Task 19: Rekap harian/mingguan/bulanan + filter (admin/wali/bk)

**Description:** Halaman rekap dengan filter tanggal (hari/minggu/bulan), kelas, jurusan, status; hierarki kolom sesuai desain §7.8 (kehadiran = gerbang, rincian mapel, pulang); tampilan ringkas + detail per siswa (panel KPI & tabel mengikuti gaya mockup dashboard).

**Acceptance criteria:**
- [ ] Rekap harian per kelas: semua siswa + status gerbang/mapel/pulang (kolom `-` bila gerbang izin/sakit/alpa)
- [ ] Rekap mingguan & bulanan akurat (jumlah H/T/I/S/A + % kehadiran) — diverifikasi terhadap data manual
- [ ] Filter & paginasi berfungsi; hak akses per role (admin/bk semua, wali kelas kelasnya)

**Verification:**
- [ ] Test: agregasi angka rekap dengan fixture data
- [ ] Manual: cross-check hitungan dengan query mentah

**Dependencies:** Task 12, Task 14
**Files likely touched:** `app/Http/Controllers/RekapController.php`, `app/Services/Rekap/*`, `resources/views/rekap/*`
**Estimated scope:** L

## Task 20: Ekspor PDF & XLSX berkop sekolah

**Description:** Tombol ekspor di halaman rekap: PDF (dompdf, kop `setting_sekolah`, tanda tangan) dan XLSX (maatwebsite/excel) untuk rentang harian/mingguan/bulanan/semester.

**Acceptance criteria:**
- [ ] PDF berkop sekolah + tabel rekap + footer tanggal cetak terunduh
- [ ] XLSX terunduh dengan header & data sama dengan tampilan
- [ ] Hanya role berwenang (admin, wali kelas kelasnya, bk) yang bisa ekspor

**Verification:**
- [ ] Test: endpoint ekspor mengembalikan content-type benar
- [ ] Manual: buka hasil PDF & XLSX, cocokkan isi

**Dependencies:** Task 19
**Files likely touched:** `app/Services/Ekspor/{PdfRekap,XlsxRekap}.php`, `resources/views/rekap/pdf/*`
**Estimated scope:** M

## Task 21: Dashboard admin KPI + laporan wali kelas & BK (siswa <90%)

**Description:** Dashboard admin mengikuti mockup `dashboard_admin_rekapitulasi`: sub-header sapaan + aksi cepat, 6 KPI bento, filter bar, tabel log real-time, panel kanan (Verifikasi Izin & Sakit, Antrean Koreksi, Monitoring Engine). Plus laporan wali kelas (kelasnya) & BK (semua kelas) + daftar siswa bermasalah (<90% / alpa berulang).

**Acceptance criteria:**
- [ ] KPI terhitung dari data hari ini & ter-update setelah scan
- [ ] Antrean approval dari dua sumber (izin, koreksi) bisa dibuka langsung
- [ ] Laporan BK: filter siswa <90%, urutkan paling parah, lihat rincian 3 jenis absen

**Verification:**
- [ ] Test: perhitungan KPI dengan fixture
- [ ] Manual: scan beberapa siswa → KPI berubah

**Dependencies:** Task 15, Task 16, Task 19
**Files likely touched:** `app/Http/Controllers/{Dashboard,LaporanBk}Controller.php`, `resources/views/dashboard/*`
**Estimated scope:** L

### Checkpoint: Output
- [ ] Rekap & ekspor sesuai spesifikasi guru; notifikasi tercatat per event · **review manusia**

## Phase 6: Finalisasi

## Task 22: Seeder demo data UJIKOM

**Description:** Seeder lengkap: admin, guru+wali kelas, osis/piket, ortu berlinking, 3 jurusan, ~6 kelas, ~120 siswa (QR unique), jadwal seminggu penuh (termasuk Jumat pendek), `pengaturan_hari` default, hari libur contoh, plus data absensi 2 minggu untuk rekap & ekspor.

**Acceptance criteria:**
- [ ] `migrate:fresh --seed` sekali jalan menghasilkan data demo konsisten (tidak bentrok jadwal)
- [ ] Data cukup untuk mendemokan rekap, ekspor, laporan BK, dan dashboard
- [ ] Akun demo tiap role tercetak di README (hanya untuk local)

**Verification:**
- [ ] Test: seeder idempoten (boleh dijalankan 2×)
- [ ] Manual: login tiap role → semua halaman terisi data

**Dependencies:** Task 21
**Files likely touched:** `database/seeders/*`
**Estimated scope:** M

## Task 23: Verifikasi end-to-end + Pint + README panduan demo UJIKOM

**Description:** Jalankan seluruh alur Ujikom sekali penuh (siapkan data → scan masuk → absen mapel → approve izin → koreksi → scan pulang → auto-alpa/kabur → rekap → ekspor), perbaiki temuan, format dengan Pint, rapikan test, tulis README: setup, kredensial demo, alur presentasi, batasan scope.

**Acceptance criteria:**
- [ ] Semua test hijau, `vendor/bin/pint --test` bersih, `npm run build` sukses
- [ ] Skenario Ujikom E2E lulus tanpa intervensi database
- [ ] README memuat: instalasi, setting jam per hari, cron/scheduler, akun demo, batasan scope

**Verification:**
- [ ] Test: full suite
- [ ] Manual: ikuti README dari nol di environment bersih

**Dependencies:** Task 22
**Files likely touched:** `README.md`, `tests/**`, perbaikan kecil lintas app
**Estimated scope:** L

### Checkpoint: Complete
- [ ] Semua acceptance criteria terpenuhi · siap presentasi Ujikom
