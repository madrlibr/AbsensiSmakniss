<?php

namespace App\Support;

use App\Models\User;

/**
 * Menu sidebar per role (sumber kebenaran tunggal navigasi shell).
 */
class Menu
{
    /**
     * @return array<string, array<int, array{icon: string, label: string, route: string, badge?: string}>>
     */
    public static function untuk(?User $user): array
    {
        $role = $user?->role?->value;

        $groups = [
            'Operasional Harian' => [
                // Dashboard staf — ortu punya Beranda sendiri di grup portal
                ['icon' => 'analytics', 'label' => 'Dashboard Monitoring', 'route' => 'dashboard', 'roles' => ['admin', 'guru', 'wali_kelas', 'bk', 'osis', 'piket']],
                ['icon' => 'qr_code_scanner', 'label' => 'Scanner Gerbang', 'route' => 'scan.gerbang', 'roles' => ['admin', 'osis', 'piket']],
                ['icon' => 'logout', 'label' => 'Scanner Pulang', 'route' => 'scan.pulang', 'roles' => ['admin', 'osis', 'piket']],
                ['icon' => 'menu_book', 'label' => 'Presensi Mapel', 'route' => 'absensi.mapel', 'roles' => ['admin', 'guru', 'wali_kelas']],
            ],
            'Laporan & Administrasi' => [
                ['icon' => 'description', 'label' => 'Rekap Kehadiran', 'route' => 'rekap.index', 'roles' => ['admin', 'wali_kelas', 'bk']],
                ['icon' => 'fact_check', 'label' => 'Laporan BK', 'route' => 'laporan.bk', 'roles' => ['admin', 'bk']],
                ['icon' => 'assignment_turned_in', 'label' => 'Pengajuan Izin & Sakit', 'route' => 'pengajuan.index', 'roles' => ['admin', 'wali_kelas'], 'badge' => 'pengajuan_pending'],
                ['icon' => 'rule', 'label' => 'Koreksi Data', 'route' => 'koreksi.index', 'roles' => ['admin', 'wali_kelas', 'bk', 'guru', 'osis', 'piket']],
            ],
            'Portal Orang Tua' => [
                ['icon' => 'home', 'label' => 'Beranda', 'route' => 'portal.index', 'roles' => ['orang_tua']],
                ['icon' => 'event_note', 'label' => 'Riwayat Absensi', 'route' => 'portal.riwayat', 'roles' => ['orang_tua']],
                ['icon' => 'edit_document', 'label' => 'Ajukan Izin & Sakit', 'route' => 'portal.pengajuan', 'roles' => ['orang_tua']],
                ['icon' => 'notifications', 'label' => 'Notifikasi', 'route' => 'portal.notifikasi', 'roles' => ['orang_tua']],
            ],
            'Master Data Sekolah' => [
                ['icon' => 'calendar_month', 'label' => 'Tahun Ajaran', 'route' => 'master.tahun-ajaran', 'roles' => ['admin']],
                ['icon' => 'menu_book', 'label' => 'Data Mata Pelajaran', 'route' => 'master.mapel', 'roles' => ['admin']],
                ['icon' => 'category', 'label' => 'Data Jurusan', 'route' => 'master.jurusan', 'roles' => ['admin']],
                ['icon' => 'meeting_room', 'label' => 'Data Kelas', 'route' => 'master.kelas', 'roles' => ['admin']],
                ['icon' => 'how_to_reg', 'label' => 'Data Siswa', 'route' => 'master.siswa', 'roles' => ['admin']],
                ['icon' => 'badge', 'label' => 'Data Guru', 'route' => 'master.guru', 'roles' => ['admin']],
            ],
            'Manajemen Sistem' => [
                ['icon' => 'event', 'label' => 'Jadwal & Kalender', 'route' => 'jadwal.index', 'roles' => ['admin', 'wali_kelas']],
                ['icon' => 'tune', 'label' => 'Pengaturan Jam Presensi', 'route' => 'pengaturan.hari', 'roles' => ['admin']],
                ['icon' => 'beach_access', 'label' => 'Hari Libur Rentang', 'route' => 'pengaturan.libur', 'roles' => ['admin']],
            ],
        ];

        $hasil = [];
        foreach ($groups as $label => $items) {
            $terlihat = array_values(array_filter(
                $items,
                fn (array $item): bool => $item['roles'] === 'all' || in_array($role, $item['roles'], true)
            ));

            if ($terlihat !== []) {
                $hasil[$label] = $terlihat;
            }
        }

        return $hasil;
    }

    /**
     * Badge hitung-angka pada item menu (mis. antrean pengajuan pending).
     * Sumber data diisi per tugas; sementara null = tanpa badge.
     */
    public static function hitungBadge(string $badge): ?int
    {
        return match ($badge) {
            // Task 15: antrean pengajuan izin/sakit wajib disetujui
            'pengajuan_pending' => null,
            default => null,
        };
    }
}
