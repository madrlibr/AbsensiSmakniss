# DESAIN UI/UX SISTEM ABSENSI SISWA — UJIKOM (SMK)

> Dokumen perancangan antarmuka dan pengalaman pengguna. **FIXED** — siap untuk eksekusi implementasi frontend.

---

## 1. Konsep Desain & Gaya Visual

| Aspek | Rekomendasi | Alasan |
|-------|-------------|--------|
| **Gaya Umum** | Clean, Professional, Educational | Mudah dipahami oleh semua usia, tidak berlebihan |
| **Layout Desktop** | Sidebar kiri + konten utama | Navigasi jelas untuk admin, guru, wali kelas, BK |
| **Layout Mobile** | Bottom navigation drawer | Mudah dicapai satu tangan (thumb zone) |
| **Tema Warna** | Biru Sekolah (menyesuaikan identitas sekolah) | Konsisten dengan branding sekolah |
| **Typography** | Sans-serif bersih (system-ui, Inter, dll) | Mudah dibaca di layar kecil dan besar |
| **Ikon** | Heroicons / Lucide | Ringan, konsisten, cocok untuk antarmuka administratif |
| **Komponen** | Card-based, shadow halus, border-radius 8-12px | Modern, mudah dipindai informasi |

---

## 2. Palet Warna (Tema Biru Sekolah)

Warna utama: **`#2563EB` (primary-500 — Biru Sekolah Standar)**

| Nama | Kode Hex | Penggunaan |
|------|----------|------------|
| `primary-50` | `#eff6ff` | Background lembut |
| `primary-100` | `#dbeafe` | Badge, card sekunder |
| `primary-500` | `#2563eb` | Tombol utama, tautan aktif, header |
| `primary-600` | `#1d4ed8` | Hover tombol utama |
| `primary-800` | `#1e3a8a` | Sidebar aktif, teks kontras |
| `success-500` | `#10b981` | Status Hadir, Sukses |
| `success-600` | `#059669` | Hover status hadir |
| `warning-500` | `#f59e0b` | Status Terlambat, Pulang Cepat |
| `warning-600` | `#d97706` | Hover status terlambat |
| `danger-500` | `#ef4444` | Status Alpa, Kabur, Tolak |
| `danger-600` | `#dc2626` | Hover status alpa/kabur |
| `info-500` | `#3b82f6` | Status Izin, Sakit, Informasi |
| `info-600` | `#2563eb` | Hover status izin/sakit |
| `dark-900` | `#0f172a` | Teks utama, sidebar |
| `light-50` | `#f8fafc` | Background halaman |
| `white` | `#FFFFFF` | Kartu, dropdown, modal |

### Opsi Variasi Biru (pilih sesuai sekolah):

| Opsi | Kode | Kesan |
|------|------|-------|
| Biru Langit | `#3B82F6` | Segar, modern, ramah |
| **Biru Sekolah Standar** | `#2563EB` | Seimbang, standar pendidikan **(Direkomendasikan)** |
| Biru Tua/Navy | `#1E3A8A` | Formal, profesional |

---

## 3. Struktur Navigasi per Role

### Desktop — Sidebar Kiri
```
┌─────────────────────────────────────┐
│  [Logo]  SISTEM ABSENSI SMK         │
├─────────────────────────────────────┤
│  Dashboard                          │
│  Master Data                        │
│   ├─ Tahun Ajaran                   │
│   ├─ Jurusan                        │
│   ├─ Kelas                          │
│   ├─ Mapel                          │
│   ├─ Guru                           │
│   ├─ Siswa                          │
│   └─ Orang Tua                      │
│  Absensi                            │
│   ├─ Absen Gerbang (OSIS/Piket)     │
│   ├─ Absen Mapel (Guru/Wali)        │
│   └─ Absen Pulang (OSIS/Piket)      │
│  Pengajuan                          │
│   ├─ Izin/Sakit (Ortu/Wali Kelas)   │
│   └─ Koreksi Absensi                │
│  Laporan                            │
│   ├─ Rekap Harian                   │
│   ├─ Rekap Mingguan                 │
│   └─ Rekap Bulanan                  │
│  Setting                            │
│   ├─ Konfigurasi Absensi            │
│   └─ Hari Libur                     │
├─────────────────────────────────────┤
│  [Pengaturan Akun]  [Logout]        │
└─────────────────────────────────────┘
```

### Mobile — Bottom Navigation (OSIS & Orang Tua)
```
┌─────────────────────────────────────┐
│  [Logo]        [Menu]  [Profil]     │
├─────────────────────────────────────┤
│  Konten Halaman Utama               │
│  (Scan, Form, Tabel, dll)           │
│                                     │
│                                     │
│                                     │
├─────────────────────────────────────┤
│ [Beranda] [Scan] [Laporan] [Akun]   │  ← Bottom Nav (Thumb Zone)
└─────────────────────────────────────┘
```

---

## 4. Desain Per Halaman (Wireframe Konseptual)

### 4.1 Login Halaman (Semua Role)
- **Desktop:** Center card di tengah, background gradasi biru lembut
- **Mobile:** Full width card, tombol login lebar penuh
- Elemen: Logo sekolah, input Email & Password, tombol Masuk

### 4.2 Dashboard (Admin / Guru / Wali Kelas / BK)
- **Desktop:** Grid cards statistik (Hari Ini: Hadir, Terlambat, Alpa, Kabur), tabel rekap terbaru
- **Mobile:** Stacked cards (satu per satu), tabel di-scroll horizontal

### 4.3 Absen Gerbang (OSIS/Piket) — **MOBILE FIRST**
```
┌─────────────────────────────────────┐
│  ABSEN GERBANG                      │
├─────────────────────────────────────┤
│                                     │
│           [ SCAN KARTU ]            │
│                                     │
│  [ Input Manual NIS/NISN ]          │
│                                     │
│  Data Terakhir:                     │
│  Budi Santoso - X TKJ 1             │
│  Hadir Tepat Waktu                  │
│  07:15 WIB                          │
└─────────────────────────────────────┘
```
- Tombol scan besar, mudah dicapai jempol
- Status indicator warna: Hijau (Hadir), Kuning (Terlambat), Merah (Diblokir)

### 4.4 Absen Mapel (Guru) — **MOBILE FRIENDLY**
- Pilih kelas & jadwal pelajaran
- Daftar siswa dalam bentuk kartu/baris
- Setiap siswa: Nama, NIS, tombol status (Hadir/Terlambat/Izin/Sakit/Alpa)
- Tombol Simpan di bagian bawah (sticky)

### 4.5 Absen Pulang (OSIS/Piket) — **MOBILE FIRST**
- Sama seperti absen gerbang, untuk pulang
- Window waktu scan ditampilkan (contoh: 14:00 – 14:50)
- Status: Pulang, Pulang Cepat, Kabur (otomatis)

### 4.6 Pulang Cepat (Piket)
- Form: pilih siswa, alasan (sakit/izin keluarga/lainnya)
- Buat karcis/surat kecil (dicetak atau ditampilkan di layar)
- Siswa menunjukkan karcis ke penjaga gerbang saat keluar

### 4.7 Pengajuan Izin/Sakit (Orang Tua) — **MOBILE FIRST**
```
┌─────────────────────────────────────┐
│  PENGAJUAN IZIN / SAKIT             │
├─────────────────────────────────────┤
│  Jenis:     [ Izin  ● Sakit  ○ ]    │
│  Tanggal:   [ 26-10-2026  s.d  27 ] │
│  Alasan:    [ ____________________ ]│
│  Foto:      [ Upload Surut Sakit ]  │
│                                     │
│           [ KIRIM PENGAJUAN ]       │
└─────────────────────────────────────┘
```
- Upload foto via kamera atau galeri HP
- Riwayat pengajuan dengan badge status (Pending, Disetujui, Ditolak)

### 4.8 Approval Izin (Wali Kelas)
- Daftar pengajuan pending dari orang tua
- Kartu tiap pengajuan: Nama siswa, jenis, tanggal, foto bukti
- Tombol Setujui / Tolak + kolom catatan

### 4.9 Rekapitulasi (Harian / Mingguan / Bulanan)
- Filter: Tanggal, Kelas, Jurusan, Status, Tahun Ajaran
- Tabel data absensi dengan filter
- Tombol Export PDF / Excel
- Ringkasan statistik di atas tabel

### 4.10 Setting & Hari Libur
- Form input jam toleransi, batas waktu, jam alpa, jendela pulang
- Pengaturan hari libur rutin (checkbox Sabtu, Minggu)
- Daftar hari libur rentang tanggal

---

## 5. Komponen UI Utama

| Komponen | Desain | Penggunaan |
|----------|--------|------------|
| **Tombol Primary** | Biru `#2563EB`, teks putih, border-radius 8px, padding 12px 24px | Aksi utama (Simpan, Scan, Kirim) |
| **Tombol Secondary** | Putih, border abu-abu, teks gelap | Aksi sekunder (Batal, Kembali) |
| **Tombol Danger** | Merah `#EF4444`, teks putih | Hapus, Tolak |
| **Badge Status** | Pills dengan warna sesuai status | Hadir (Hijau), Terlambat (Kuning), Alpa (Merah) |
| **Input Field** | Border abu-abu lembut, focus ring biru, label di atas | Form input |
| **Card** | Background putih, shadow halus, border-radius 12px, padding 20px | Kontainer informasi |
| **Tabel** | Striped rows, hover effect, header biru | Data master dan rekap |
| **Alert** | Banner di atas halaman dengan ikon | Pesan sukses, error, peringatan |

---

## 6. Mobile-First Strategy (OSIS & Orang Tua)

Karena halaman OSIS dan Orang Tua akan dibuka di HP, prioritas desain:

| Aspek | Implementasi Mobile-First |
|-------|---------------------------|
| **Navigasi** | Bottom navigation bar (4-5 item utama) untuk jangkauan jempol |
| **Tombol Utama** | Minimal 44×44 px (ukuran sentuh jempol), diletakkan di bawah layar |
| **Form Input** | Label di atas input, keyboard type sesuai (number untuk NIS, email untuk email) |
| **Upload Foto** | Buka kamera langsung atau galeri HP |
| **Tabel Data** | Scroll horizontal, atau card per item di layar kecil |
| **Font Size** | Minimal 16px untuk input agar tidak zoom otomatis di iOS |
| **Loading State** | Spinner atau skeleton loader |
| **Feedback Sentuh** | Animasi ripple atau warna berubah saat tombol ditekan |

---

## 7. Alur Pengguna (User Journey)

### OSIS (Di HP di Gerbang)
```
Buka HP → Login → Halaman Scan Gerbang → Scan Kartu → 
Tampil Nama + Status → Kirim Notif Orang Tua (auto) → 
Siswa Berikutnya
```

### Piket (Di HP di Gerbang)
```
Buka HP → Login → Izin Pulang Cepat → Pilih Siswa + Alasan → 
Buat Karcis → Tampilkan ke Penjaga Gerbang → Kirim Notif Orang Tua
```

### Orang Tua (Di HP)
```
Buka HP → Login → Dashboard Anak → Pengajuan Izin/Sakit → 
Isi Form + Upload Foto → Kirim → Tunggu Approval → Notif Hasil
```

### Guru Mapel (Di HP/Laptop)
```
Login → Pilih Kelas & Jadwal → Daftar Siswa → 
Pilih Status per Siswa → Simpan → Notif Orang Tua (auto)
```

---

## 8. Rekomendasi Stack Teknis Frontend

| Teknologi | Versi | Alasan |
|-----------|-------|--------|
| **Tailwind CSS** | v3.4 | Default Laravel, utility-first, tema biru mudah dikonfigurasi |
| **Blade Templates** | Laravel 13 | Ringan, tidak perlu build step untuk prototipe |
| **Alpine.js** | Latest | Interaktivitas sederhana (toggle modal, dropdown) tanpa bundle JS berat |
| **Heroicons** | Latest | Ikon konsisten dan ringan |
| **Vite** | Latest | Build tool default Laravel |
| **Print CSS / DomPDF** | — | Export rekap ke PDF |

---

## 9. Checklist Desain UI/UX

- [x] Palet warna biru sekolah ditetapkan (`#2563EB` primary)
- [x] Sidebar desktop + bottom nav mobile dirancang
- [x] Halaman OSIS dirancang mobile-first (tombol scan besar, satu tangan)
- [x] Halaman Orang Tua dirancang mobile-first (form izin, upload foto, riwayat)
- [x] Halaman Piket untuk pulang cepat + karcis
- [x] Semua role memiliki navigasi sesuai hak akses
- [x] Status kehadiran memiliki warna visual berbeda (Hijau/Kuning/Merah/Biru)
- [x] Tombol aksi utama mudah dijangkau di layar HP
- [x] Tabel data responsif (scroll horizontal atau card view di mobile)
- [x] Feedback visual saat tombol ditekan (active state)

---

Dibuat untuk UJIKOM — Sistem Absensi Siswa (Laravel, SMK).