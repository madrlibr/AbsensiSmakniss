<?php

namespace Tests\Unit;

use App\Enums\StatusAbsensi;
use App\Models\PengaturanHari;
use App\Services\Absensi\GerbangRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 10 — Aturan gerbang masuk (desain §7.2).
 * Default Senin (2 Nov 2026): buka 06:45, batas 07:00, tutup 07:30.
 */
class GerbangRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PengaturanHari::kembalikanDefault();
    }

    private function rules(): GerbangRules
    {
        return app(GerbangRules::class);
    }

    public function test_sebelum_buka_diblokir(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-02 06:00');

        $this->assertFalse($hasil->izinkan);
        $this->assertStringContainsString('Gerbang belum dibuka', $hasil->pesanBlokir);
        $this->assertStringContainsString('06:45', $hasil->pesanBlokir);
    }

    public function test_tepat_di_buka_adalah_hadir_tepat_waktu(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-02 06:45');

        $this->assertTrue($hasil->izinkan);
        $this->assertSame(StatusAbsensi::HadirTepatWaktu, $hasil->status);
        $this->assertFalse($hasil->keteranganWajib);
    }

    public function test_tepat_di_batas_masih_tepat_waktu(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-02 07:00');

        $this->assertSame(StatusAbsensi::HadirTepatWaktu, $hasil->status);
    }

    public function test_satu_menit_lewat_batas_jadi_terlambat(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-02 07:01');

        $this->assertTrue($hasil->izinkan);
        $this->assertSame(StatusAbsensi::Terlambat, $hasil->status);
    }

    public function test_tepat_di_jam_tutup_masih_terlambat(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-02 07:30');

        $this->assertSame(StatusAbsensi::Terlambat, $hasil->status);
    }

    public function test_lewat_tutup_scan_qr_diblokir(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-02 07:31');

        $this->assertFalse($hasil->izinkan);
        $this->assertStringContainsString('Gerbang ditutup', $hasil->pesanBlokir);
    }

    public function test_lewat_tutup_input_petugas_diperbolehkan_dengan_keterangan_wajib(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-02 08:00', inputPetugas: true);

        $this->assertTrue($hasil->izinkan);
        $this->assertSame(StatusAbsensi::Terlambat, $hasil->status);
        $this->assertTrue($hasil->keteranganWajib);
    }

    public function test_hari_libur_scan_diblokir(): void
    {
        $hasil = $this->rules()->tentukanStatus('2026-11-07 07:00'); // Sabtu

        $this->assertFalse($hasil->izinkan);
        $this->assertStringContainsString('libur', $hasil->pesanBlokir);
    }

    public function test_jam_belum_dikonfigurasi_diblokir(): void
    {
        PengaturanHari::untuk('senin')->update([
            'buka_scan_gerbang' => null, 'batas_tepat_waktu' => null, 'jam_tutup_gerbang' => null,
            'jam_auto_alpa' => null, 'mulai_scan_pulang' => null, 'akhir_scan_pulang' => null,
        ]);

        $hasil = $this->rules()->tentukanStatus('2026-11-02 07:00');

        $this->assertFalse($hasil->izinkan);
        $this->assertSame('Jam absensi hari ini belum dikonfigurasi.', $hasil->pesanBlokir);
    }
}
