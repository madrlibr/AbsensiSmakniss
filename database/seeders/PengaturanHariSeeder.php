<?php

namespace Database\Seeders;

use App\Enums\Hari;
use App\Models\PengaturanHari;
use Illuminate\Database\Seeder;

/**
 * Task 9: 7 baris pengaturan_hari — default Senin–Jumat masuk (06:45–15:30),
 * Sabtu & Minggu libur (jam NULL). Idempoten: baris yang sudah ada dibiarkan.
 */
class PengaturanHariSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Hari::semua() as $hari) {
            PengaturanHari::firstOrCreate(
                ['hari' => $hari->value],
                PengaturanHari::defaultUntuk($hari),
            );
        }
    }
}
