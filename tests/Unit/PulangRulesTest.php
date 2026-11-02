<?php

namespace Tests\Unit;

use App\Enums\StatusAbsensi;
use App\Models\PengaturanHari;
use App\Services\Absensi\PulangRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 10 — Aturan jendela pulang (desain §7.4).
 * Default Senin (2 Nov 2026): mulai 14:00, akhir 15:30.
 */
class PulangRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PengaturanHari::kembalikanDefault();
    }

    private function rules(): PulangRules
    {
        return app(PulangRules::class);
    }

    public function test_sebelum_jendela_diblokir(): void
    {
        $hasil = $this->rules()->dalamJendela('2026-11-02 13:59');

        $this->assertFalse($hasil->izinkan);
        $this->assertStringContainsString('Belum waktunya pulang', $hasil->pesanBlokir);
        $this->assertStringContainsString('14:00–15:30', $hasil->pesanBlokir);
    }

    public function test_tepat_mulai_jendela_diizinkan(): void
    {
        $hasil = $this->rules()->dalamJendela('2026-11-02 14:00');

        $this->assertTrue($hasil->izinkan);
        $this->assertSame(StatusAbsensi::Pulang, $hasil->status);
    }

    public function test_dalam_jendela_diizinkan(): void
    {
        $hasil = $this->rules()->dalamJendela('2026-11-02 15:00');

        $this->assertSame(StatusAbsensi::Pulang, $hasil->status);
    }

    public function test_tepat_akhir_jendela_masih_diizinkan(): void
    {
        $hasil = $this->rules()->dalamJendela('2026-11-02 15:30');

        $this->assertSame(StatusAbsensi::Pulang, $hasil->status);
    }

    public function test_setelah_akhir_jendela_diblokir(): void
    {
        $hasil = $this->rules()->dalamJendela('2026-11-02 15:31');

        $this->assertFalse($hasil->izinkan);
        $this->assertStringContainsString('Jendela pulang berakhir', $hasil->pesanBlokir);
        $this->assertStringContainsString('koreksi', $hasil->pesanBlokir);
    }

    public function test_hari_libur_diblokir(): void
    {
        $hasil = $this->rules()->dalamJendela('2026-11-07 14:30'); // Sabtu

        $this->assertFalse($hasil->izinkan);
        $this->assertStringContainsString('libur', $hasil->pesanBlokir);
    }

    public function test_jam_belum_dikonfigurasi_diblokir(): void
    {
        PengaturanHari::untuk('senin')->update([
            'buka_scan_gerbang' => null, 'batas_tepat_waktu' => null, 'jam_tutup_gerbang' => null,
            'jam_auto_alpa' => null, 'mulai_scan_pulang' => null, 'akhir_scan_pulang' => null,
        ]);

        $hasil = $this->rules()->dalamJendela('2026-11-02 14:30');

        $this->assertFalse($hasil->izinkan);
        $this->assertSame('Jam absensi hari ini belum dikonfigurasi.', $hasil->pesanBlokir);
    }
}
