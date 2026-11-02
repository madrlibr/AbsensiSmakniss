<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed dasar: akun admin + tahun ajaran aktif.
     * (Data demo lengkap ada di Task 22 — DemoDataSeeder.)
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@sekolah.test'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => Role::Admin,
                'is_active' => true,
            ]
        );

        TahunAjaran::firstOrCreate(
            ['nama' => '2026/2027'],
            [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'is_active' => true,
            ]
        );

        // Task 9: pengaturan jam per hari (Senin–Jumat masuk, Sabtu–Minggu libur)
        $this->call(PengaturanHariSeeder::class);
    }
}
