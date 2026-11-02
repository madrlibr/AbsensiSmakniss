<?php

namespace Tests\Unit;

use App\Enums\StatusHari;
use App\Models\HariLibur;
use App\Models\PengaturanHari;
use App\Models\TahunAjaran;
use App\Services\Absensi\HariAktifRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 10 — Precedence hari aktif/libur (desain §7.1).
 * Referensi tanggal: 2 Nov 2026 = Senin, 4 Nov = Rabu, 5 Nov = Kamis,
 * 6 Nov = Jumat, 7 Nov = Sabtu (default: Senin–Jumat masuk, Sabtu–Minggu libur).
 */
class HariAktifRulesTest extends TestCase
{
    use RefreshDatabase;

    private TahunAjaran $ta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_active' => true,
        ]);
        PengaturanHari::kembalikanDefault();
    }

    private function rules(): HariAktifRules
    {
        return app(HariAktifRules::class);
    }

    private function libur(string $mulai, ?string $selesai, string $statusMasuk, string $keterangan, bool $aktif = true): HariLibur
    {
        return HariLibur::create([
            'tahun_ajaran_id' => $this->ta->id,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'keterangan' => $keterangan,
            'status_masuk' => $statusMasuk,
            'is_active' => $aktif,
        ]);
    }

    /* ── Kasus dasar ────────────────────────────────────────── */

    public function test_hari_biasa_terkonfigurasi_adalah_aktif(): void
    {
        $hasil = $this->rules()->status('2026-11-02'); // Senin

        $this->assertSame(StatusHari::Aktif, $hasil->status);
        $this->assertTrue($hasil->bolehAbsensi());
        $this->assertTrue($hasil->bolehDikerjakanJob());
        $this->assertNull($hasil->pesanBlokir);
        $this->assertSame('07:00', $hasil->pengaturan->batas_tepat_waktu->format('H:i'));
    }

    public function test_libur_rutin_sabtu_terblokir(): void
    {
        $hasil = $this->rules()->status('2026-11-07'); // Sabtu

        $this->assertSame(StatusHari::LiburRutin, $hasil->status);
        $this->assertFalse($hasil->bolehAbsensi());
        $this->assertFalse($hasil->bolehDikerjakanJob());
        $this->assertStringContainsString('libur sesuai pengaturan', $hasil->pesanBlokir);
    }

    public function test_jam_kosong_memblokir_scan(): void
    {
        PengaturanHari::untuk('senin')->update([
            'buka_scan_gerbang' => null, 'batas_tepat_waktu' => null, 'jam_tutup_gerbang' => null,
            'jam_auto_alpa' => null, 'mulai_scan_pulang' => null, 'akhir_scan_pulang' => null,
        ]);

        $hasil = $this->rules()->status('2026-11-02');

        $this->assertSame(StatusHari::Aktif, $hasil->status);
        $this->assertFalse($hasil->bolehAbsensi());
        $this->assertSame('Jam absensi hari ini belum dikonfigurasi.', $hasil->pesanBlokir);
    }

    /* ── Libur rentang (precedence1 &2) ────────────────────── */

    public function test_libur_mutlak_rentang_tidak_masuk(): void
    {
        $this->libur('2026-11-02', '2026-11-04', 'tidak_masuk', 'Libur Proklamasi');

        $hasil = $this->rules()->status('2026-11-03');

        $this->assertSame(StatusHari::LiburMutlak, $hasil->status);
        $this->assertFalse($hasil->bolehAbsensi());
        $this->assertFalse($hasil->bolehDikerjakanJob());
        $this->assertStringContainsString('Libur Proklamasi', $hasil->pesanBlokir);
        $this->assertSame('Libur Proklamasi', $hasil->keteranganLibur);
    }

    public function test_libur_mutlak_tidak_menjangkau_tanggal_di_luar_rentang(): void
    {
        $this->libur('2026-11-02', '2026-11-04', 'tidak_masuk', 'Libur Proklamasi');

        $this->assertSame(StatusHari::Aktif, $this->rules()->status('2026-11-06')->status);
    }

    public function test_libur_satu_hari_hanya_menempati_tanggalnya(): void
    {
        $this->libur('2026-11-05', null, 'tidak_masuk', 'Guru Training'); // Kamis, selesai NULL

        $this->assertSame(StatusHari::LiburMutlak, $this->rules()->status('2026-11-05')->status);
        // Jumat setelahnya TIDAK ikut libur (regresi rentang null)
        $this->assertSame(StatusHari::Aktif, $this->rules()->status('2026-11-06')->status);
    }

    public function test_kegiatan_pada_hari_libur_rutin_paksa_aktif_tapi_jam_kosong(): void
    {
        $this->libur('2026-11-07', null, 'masuk', 'Latihan OSIS'); // Sabtu

        $hasil = $this->rules()->status('2026-11-07');

        $this->assertSame(StatusHari::PaksaAktif, $hasil->status);
        // Sabtu default jam NULL → admin wajib mengisi jam hari itu (§7.1 poin 2)
        $this->assertFalse($hasil->bolehAbsensi());
        $this->assertSame('Jam absensi hari ini belum dikonfigurasi.', $hasil->pesanBlokir);
    }

    public function test_kegiatan_pada_hari_kerja_tetap_boleh_absen(): void
    {
        $this->libur('2026-11-04', null, 'masuk', 'Uji Tengah Semester'); // Rabu

        $hasil = $this->rules()->status('2026-11-04');

        $this->assertSame(StatusHari::PaksaAktif, $hasil->status);
        $this->assertTrue($hasil->bolehAbsensi());
        $this->assertNull($hasil->pesanBlokir);
    }

    public function test_precedence_libur_mutlak_di_atas_kegiatan(): void
    {
        $this->libur('2026-11-02', '2026-11-06', 'tidak_masuk', 'Libur Nasional');
        $this->libur('2026-11-04', null, 'masuk', 'Kegiatan Terjadwal');

        $this->assertSame(StatusHari::LiburMutlak, $this->rules()->status('2026-11-04')->status);
    }

    public function test_hari_libur_nonaktif_diabaikan(): void
    {
        $this->libur('2026-11-03', null, 'tidak_masuk', 'Dibatalkan', aktif: false);

        $this->assertSame(StatusHari::Aktif, $this->rules()->status('2026-11-03')->status);
    }
}
