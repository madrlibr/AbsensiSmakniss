# PRODUCT REQUIREMENT DOCUMENT (PRD)
## SMK Attendance OS — Sistem Presensi Terintegrasi Gerbang, Mapel & Portal Wali Murid

---

### 1. Executive Summary & Ringkasan Proyek
**SMK Attendance OS** adalah sistem informasi manajemen presensi modern berbasis multi-tier yang dirancang khusus untuk memenuhi standar pengujian Uji Kompetensi Keahlian (Ujikom) Jurusan Rekayasa Perangkat Lunak (RPL) dan Teknik Komputer & Jaringan (TKJ), sekaligus siap digunakan di lingkungan SMK nyata dengan throughput tinggi (1.000+ siswa setiap pagi).

Sistem ini memecahkan masalah pencatatan manual, kecurangan absensi, siswa membolos/kabur setelah tap pagi, keterlambatan informasi ke orang tua murid, dan beban administrasi rekapitulasi guru serta petugas piket.

---

### 2. Sasaran & Key Performance Indicators (KPI)
- **High-Speed Throughput Scanner Gerbang:** Kecepatan verifikasi ≤ 0.8 detik per siswa (QR code / Barcode kartu pelajar / RFID MIFARE).
- **Otomasi Notifikasi Wali Murid:** Pengiriman konfirmasi WhatsApp via Official Gateway ≤ 3 detik setelah tap kartu.
- **Deteksi Anti-Kabur (Bolos Siang):** Rekonsiliasi otomatis 100% siswa yang tap masuk pagi tapi tidak tap pulang tanpa tiket dispensasi resmi.
- **Zero Data Loss & Resilience:** Sinkronisasi daemon offline-ready saat jaringan gerbang terputus sementara.
- **Akurasi Rekapitulasi Otomatis:** Menghilangkan 100% human-error rekap bulanan untuk buku rapor dan audit Bimbingan Konseling (BK).

---

### 3. User Personas & Stakeholders

| Persona | Peran Utama | Perangkat | Kebutuhan Kunci |
|---|---|---|---|
| **Petugas Gerbang & OSIS** | Scanning kartu siswa pagi & pulang | Mobile PWA / Smartphone Kamera / USB RFID Scanner | Viewfinder cepat, feedback suara/haptic, input dispensasi kilat |
| **Guru Mata Pelajaran (Mapel)** | Presensi jam pelajaran di kelas | Laptop / Tablet | Checklist 1-klik "Set Semua Hadir", input catatan siswa |
| **Wali Kelas & Guru BK** | Monitoring, tindak lanjut, & rekap | Desktop Web | Filter absensi kelas, approval surat dokter/izin, log siswa bermasalah |
| **Admin IT & Kepala Sekolah** | Konfigurasi sistem & eksekutif overview | Desktop Web | Manajemen cron daemon, aturan jam masuk/toleransi, ekspor laporan resmi |
| **Orang Tua / Wali Murid** | Memantau kehadiran anak & mengajukan izin | Mobile Smartphone | Tampilan ramah keluarga, linimasa riwayat tap, upload bukti surat sakit |

---

### 4. Arsitektur Sistem & Alur Bisnis Presensi

```
[ Siswa Datang ] 
       │
       ▼
[ Gate In Scanner ] ──(06:30 - 07:15)──► Status: HADIR TEPAT WAKTU ──► Auto WA ke Ortu
       │
       ├──────(07:16 - 07:30)────────► Status: TERLAMBAT (+Piket) ───► Auto WA Keterlambatan
       │
       └──────(Lewat 07:30)──────────► Gerbang Ditutup ──────────────► Pos Piket Khusus
                                              │
[ Cron Daemon 08:00 WIB ] ─────────────────────┼──────────────────────► Auto-Alpa bagi Siswa Tanpa Tap
                                              │
[ Jam Pelajaran Kelas ] ──────────────────────┼──────────────────────► Guru Mapel Verifikasi Fisik
                                              │
[ Window Pulang (14:00 - 15:30) ] ────────────┴──────────────────────► Gate Out Scanner 
                                                                              │
                                                              [ Jika Tidak Tap Pulang ]
                                                                              │
                                                                              ▼
                                                                  Engine Deteksi "Auto-Kabur"
```

---

### 5. Modul Fungsional & Spesifikasi Antarmuka

#### Modul 1: Dashboard Admin & Rekapitulasi Presensi (Desktop)
- **Ringkasan Indikator Kehadiran Real-Time:** Total siswa aktif (1.024), Tepat Waktu (890), Terlambat (42), Sakit/Izin (38), Alpa (14), Pulang Cepat (5).
- **Tabel Log Real-Time:** Filter dinamis berjenjang (Tingkat Kelas, Jurusan, Status Presensi, Sesi).
- **Persetujuan Izin & Sakit Terpadu:** Validasi digital bukti surat dokter RS/Klinik dengan aksi 1-klik *Setujui* atau *Tolak*.
- **Antrean Koreksi Data:** Validasi revisi status dari guru mata pelajaran ke wali kelas.
- **Ekspor Dokumen:** Format siap cetak PDF resmi sekolah dan XLSX rekapitulasi per semester.

#### Modul 2: Scanner Gerbang OSIS & Piket (Mobile View)
- **Viewfinder Kamera & RFID Sensor:** Target bidik presisi tinggi, tombol senter LED darurat, toggle orientasi kamera.
- **Feedback Audio & Visual Instan:**
  - Hijau: Terverifikasi Hadir Tepat Waktu.
  - Kuning: Terlambat Masuk (otomatis tampil pop-up dialog alasan).
  - Merah: Kartu tidak aktif / terblokir / Alpa.
- **Tindakan Cepat Lapangan:**
  - *Toleransi Izin:* Untuk siswa dengan alasan kendala transportasi/banjir yang disahkan guru piket.
  - *Input Manual:* Pencarian cepat nama/NISN jika kartu siswa tertinggal atau rusak.
  - *Karcis Pulang Cepat:* Penerbitan barcode surat jalan darurat bagi siswa sakit di UKS.

#### Modul 3: Portal Orang Tua & Wali Siswa (Mobile View)
- **Banner Status Siswa Terkini:** Menampilkan kepastian keberadaan siswa di lingkungan sekolah secara jelas dan menenangkan.
- **Linimasa Perjalanan Siswa 3 Titik Pantau:**
  1. *Gerbang Masuk:* Jam scan dan konfirmasi pengiriman WA.
  2. *Presensi Mapel Kelas:* Status per mata pelajaran (misal: Pemrograman Web, Basis Data).
  3. *Gerbang Kepulangan:* Status menunggu atau telah tap keluar sekolah.
- **Layanan Cepat Mandiri:** Formulir pengajuan izin/sakit mandiri dengan unggah berkas foto surat dokter.
- **Statistik Akumulatif Kehadiran:** Tingkat kehadiran persentase (97.4%) dan indikator keselamatan batas kelulusan sekolah (minimal 90%).

#### Modul 4: Konfigurasi Jam Presensi & Aturan Otomatis (Desktop)
- **Pengaturan Waktu:** Batas tepat waktu (`07:00 WIB`), toleransi gerbang (`+15 Menit`), jam tutup gerbang (`07:30 WIB`), dan cron job alpa (`08:00 WIB`).
- **Aturan Kepulangan Berjenjang:** Jadwal reguler (Senin–Kamis 8 JP, window 14:00 - 15:30 WIB) vs Jadwal hari ibadah (Jumat pendek, window 11:30 - 12:45 WIB).
- **Engine Deteksi Auto-Kabur:** Algoritma pemadanan otomatis siswa yang masuk tetapi meninggalkan sekolah tanpa izin piket.
- **WhatsApp Gateway Dispatcher & Simulator:** Konfigurasi trigger otomatis beserta live visual bubble chat preview variabel dinamis `{{nama_siswa}}`, `{{jam_alpa}}`, dan `{{status}}`.

---

### 6. Desain Database & Skema Entitas Relasional (ERD Highlights)

1. **`tb_siswa`**: `nisn` (PK), `nama_lengkap`, `id_kelas`, `rfid_uid`, `barcode_kode`, `no_wa_ortu`, `status_aktif`.
2. **`tb_presensi_harian`**: `id_presensi` (PK), `nisn` (FK), `tanggal`, `jam_masuk`, `jam_pulang`, `status_kehadiran` (Hadir/Terlambat/Izin/Sakit/Alpa/Kabur), `petugas_scan_id`.
3. **`tb_presensi_mapel`**: `id_presensi_mapel` (PK), `nisn` (FK), `id_jadwal` (FK), `jam_ke`, `status`, `catatan_guru`.
4. **`tb_pengajuan_izin`**: `id_izin` (PK), `nisn` (FK), `kategori` (Sakit/Izin), `tgl_mulai`, `tgl_selesai`, `file_surat_url`, `status_approval` (Pending/Disetujui/Ditolak), `catatan_wali_kelas`.
5. **`tb_setting_absensi`**: `batas_tepat_waktu`, `menit_toleransi`, `jam_tutup_gerbang`, `jam_cron_alpa`, `window_pulang_reguler`, `window_pulang_jumat`, `auto_kabur_active`.
6. **`tb_wa_queue`**: `id_queue`, `nomor_tujuan`, `pesan_text`, `status_terkirim`, `timestamp_sent`, `gateway_response`.

---

### 7. Kriteria Penerimaan & Checklist Uji Ujikom
- [x] Siswa melakukan tap kartu pelajar di gerbang dan status langsung berubah menjadi Hadir Tepat Waktu.
- [x] WhatsApp otomatis diterima oleh nomor wali murid yang terdaftar dalam rentang waktu < 5 detik.
- [x] Siswa yang melakukan scan setelah pukul 07:00 namun sebelum 07:15 otomatis bertanda Terlambat dan tercatat di buku piket.
- [x] Siswa yang tidak melakukan tap hingga pukul 08:00 WIB otomatis diubah statusnya menjadi Alpa oleh cron service dan orang tua menerima pesan peringatan darurat.
- [x] Wali murid dapat mengunggah bukti surat sakit melalui portal mobile dan langsung muncul di antrean approval Dashboard Admin.
- [x] Siswa yang masuk pagi tetapi tidak melakukan tap pulang saat window berakhir otomatis tercatat pada log *Auto-Kabur Detector*.
- [x] Guru piket dan admin dapat mengunduh rekapitulasi presensi dalam format PDF/Excel dengan header resmi sekolah.

---
*Dokumen ini merupakan spesifikasi resmi perancangan antarmuka dan implementasi sistem SMK Attendance OS.*
