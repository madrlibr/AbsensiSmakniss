<?php

namespace Tests\Feature;

use App\Models\AbsensiGerbang;
use App\Models\HariLibur;
use App\Models\PengaturanHari;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 11 — Scanner gerbang masuk: endpoint scan (QR & manual petugas),
 * idempoten, aturan jam (Task 10), hari libur, siswa nonaktif.
 */
class ScanGerbangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PengaturanHari::kembalikanDefault();
    }

    private function petugas(string $role = 'piket'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function siswa(string $nisn = '0081234567'): Siswa
    {
        return Siswa::create([
            'nisn' => $nisn,
            'nis' => '2026.'.rand(100, 999),
            'nama' => 'Muhammad Rizky Pratama',
            'jenis_kelamin' => 'laki-laki',
        ]);
    }

    private function jam(string $waktu): void
    {
        Carbon::setTestNow(Carbon::parse($waktu));
    }

    private function barisSudahAda(Siswa $siswa, string $status): AbsensiGerbang
    {
        return AbsensiGerbang::create([
            'siswa_id' => $siswa->id,
            'tanggal' => Carbon::today(),
            'waktu_scan' => null,
            'status' => $status,
            'metode' => 'manual',
        ]);
    }

    /* ── Scan QR sesuai aturan waktu ─────────────────────────── */

    public function test_scan_qr_valid_pagi_mencatat_hadir_tepat_waktu(): void
    {
        $this->jam('2026-11-02 06:50'); // Senin, dalam toleransi
        $siswa = $this->siswa();
        $piket = $this->petugas();

        $res = $this->actingAs($piket)->postJson(route('scan.gerbang.proses'), [
            'kode' => $siswa->qr_code,
        ]);

        $res->assertOk()
            ->assertJsonPath('jenis', 'sukses')
            ->assertJsonPath('status', 'hadir_tepat_waktu')
            ->assertJsonPath('siswa.nama', 'Muhammad Rizky Pratama');

        $this->assertDatabaseHas('absensi_gerbang', [
            'siswa_id' => $siswa->id,
            'status' => 'hadir_tepat_waktu',
            'metode' => 'qr',
            'scanned_by' => $piket->id,
        ]);
        $this->assertNotNull(AbsensiGerbang::first()->waktu_scan);
    }

    public function test_scan_melewati_batas_mencatat_terlambat(): void
    {
        $this->jam('2026-11-02 07:10'); // lewat batas 07:00, belum tutup 07:30
        $siswa = $this->siswa();

        $this->actingAs($this->petugas())
            ->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code])
            ->assertOk()
            ->assertJsonPath('status', 'terlambat');
    }

    public function test_input_manual_nisn_dalam_jam_buka_tetap_hadir(): void
    {
        $this->jam('2026-11-02 06:55');
        $siswa = $this->siswa();

        $res = $this->actingAs($this->petugas())->postJson(route('scan.gerbang.proses'), [
            'kode' => $siswa->nisn,
            'manual' => 1,
        ]);

        $res->assertOk()->assertJsonPath('status', 'hadir_tepat_waktu');
        $this->assertDatabaseHas('absensi_gerbang', ['siswa_id' => $siswa->id, 'metode' => 'manual']);
    }

    /* ── Idempoten ───────────────────────────────────────────── */

    public function test_scan_ganda_mengembalikan_info_tanpa_duplikat(): void
    {
        $this->jam('2026-11-02 06:50');
        $siswa = $this->siswa();
        $piket = $this->petugas();

        $this->actingAs($piket)->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code])->assertOk();
        $kedua = $this->actingAs($piket)->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code]);

        $kedua->assertOk()
            ->assertJsonPath('jenis', 'info')
            ->assertJsonPath('pesan', 'Sudah absen jam 06:50 (Hadir Tepat Waktu).');

        $this->assertSame(1, AbsensiGerbang::where('siswa_id', $siswa->id)->count());
    }

    public function test_siswa_sudah_alpa_ditolak_koreksi_saja(): void
    {
        $this->jam('2026-11-02 07:10');
        $siswa = $this->siswa();
        $this->barisSudahAda($siswa, 'alpa');

        $res = $this->actingAs($this->petugas())
            ->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422)
            ->assertJsonPath('jenis', 'tolak')
            ->assertJsonPath('siswa.nama', 'Muhammad Rizky Pratama');
        $this->assertStringContainsString('koreksi', $res->json('pesan'));
        $this->assertSame('alpa', AbsensiGerbang::first()->status->value);
    }

    public function test_siswa_izin_sakit_ditolak(): void
    {
        $this->jam('2026-11-02 06:50');
        $siswa = $this->siswa();
        $this->barisSudahAda($siswa, 'izin');

        $res = $this->actingAs($this->petugas())
            ->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('berstatus Izin', $res->json('pesan'));
    }

    /* ── Jam tutup & aturan petugas ──────────────────────────── */

    public function test_scan_setelah_tutup_ditolak(): void
    {
        $this->jam('2026-11-02 07:45');
        $siswa = $this->siswa();

        $res = $this->actingAs($this->petugas('osis'))
            ->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422)->assertJsonPath('jenis', 'tolak');
        $this->assertStringContainsString('Gerbang ditutup', $res->json('pesan'));
        $this->assertDatabaseCount('absensi_gerbang', 0);
    }

    public function test_input_manual_piket_setelah_tutup_dengan_keterangan_diperbolehkan(): void
    {
        $this->jam('2026-11-02 07:45');
        $siswa = $this->siswa();

        $res = $this->actingAs($this->petugas())->postJson(route('scan.gerbang.proses'), [
            'kode' => $siswa->nisn,
            'manual' => 1,
            'keterangan' => 'Kartu pelajar rusak, diverifikasi wali kelas.',
        ]);

        $res->assertOk()->assertJsonPath('status', 'terlambat');
        $this->assertDatabaseHas('absensi_gerbang', [
            'siswa_id' => $siswa->id,
            'status' => 'terlambat',
            'metode' => 'manual',
            'keterangan' => 'Kartu pelajar rusak, diverifikasi wali kelas.',
        ]);
    }

    public function test_input_manual_setelah_tutup_tanpa_keterangan_ditolak(): void
    {
        $this->jam('2026-11-02 07:45');
        $siswa = $this->siswa();

        $res = $this->actingAs($this->petugas())->postJson(route('scan.gerbang.proses'), [
            'kode' => $siswa->nisn,
            'manual' => 1,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Keterangan wajib', $res->json('pesan'));
        $this->assertDatabaseCount('absensi_gerbang', 0);
    }

    public function test_osis_tidak_memiliki_jalan_manual_setelah_tutup(): void
    {
        $this->jam('2026-11-02 07:45');
        $siswa = $this->siswa();

        $res = $this->actingAs($this->petugas('osis'))->postJson(route('scan.gerbang.proses'), [
            'kode' => $siswa->nisn,
            'manual' => 1,
            'keterangan' => 'Dicoba OSIS.',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Gerbang ditutup', $res->json('pesan'));
    }

    /* ── Hari libur & identitas ──────────────────────────────── */

    public function test_scan_hari_libur_rutin_ditolak(): void
    {
        $this->jam('2026-11-07 07:00'); // Sabtu
        $siswa = $this->siswa();

        $res = $this->actingAs($this->petugas())
            ->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('libur sesuai pengaturan', $res->json('pesan'));
    }

    public function test_scan_libur_mutlak_rentang_ditolak(): void
    {
        $ta = TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30', 'is_active' => true,
        ]);
        HariLibur::create([
            'tahun_ajaran_id' => $ta->id, 'tanggal_mulai' => '2026-11-02',
            'tanggal_selesai' => '2026-11-03', 'keterangan' => 'Libur Proklamasi',
            'status_masuk' => 'tidak_masuk',
        ]);
        $this->jam('2026-11-02 06:50');
        $siswa = $this->siswa();

        $res = $this->actingAs($this->petugas())
            ->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Libur Proklamasi', $res->json('pesan'));
    }

    public function test_siswa_tidak_aktif_ditolak(): void
    {
        $this->jam('2026-11-02 06:50');
        $siswa = $this->siswa();
        $siswa->update(['active' => false]);

        $res = $this->actingAs($this->petugas())
            ->postJson(route('scan.gerbang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('tidak aktif', $res->json('pesan'));
    }

    public function test_kode_tidak_dikenal_ditolak(): void
    {
        $this->jam('2026-11-02 06:50');

        $res = $this->actingAs($this->petugas())
            ->postJson(route('scan.gerbang.proses'), ['kode' => 'SISWA-999-000000']);

        $res->assertStatus(422)->assertJsonPath('siswa', null);
        $this->assertStringContainsString('tidak ditemukan', $res->json('pesan'));
    }

    /* ── Halaman scanner ─────────────────────────────────────── */

    public function test_halaman_scanner_untuk_piket_dan_osis(): void
    {
        $this->actingAs($this->petugas('piket'))->get(route('scan.gerbang'))
            ->assertOk()
            ->assertSee('Scanner Gerbang')
            ->assertSee('Log Scan Terkini')
            ->assertSee('Input Manual');

        $this->actingAs($this->petugas('osis'))->get(route('scan.gerbang'))->assertOk();
    }

    public function test_guru_dan_wali_kelas_tidak_bisa_membuka_scanner(): void
    {
        $this->actingAs($this->petugas('guru'))->get(route('scan.gerbang'))->assertForbidden();
        $this->actingAs($this->petugas('wali_kelas'))->postJson(route('scan.gerbang.proses'), ['kode' => 'x'])->assertForbidden();
    }

    public function test_halaman_menampilkan_statistik_dan_log_scan(): void
    {
        $this->jam('2026-11-02 07:10');
        $a = $this->siswa('0081111111');
        $a->update(['nama' => 'Siti Aminah']);
        $b = $this->siswa('0082222222');
        $this->actingAs($this->petugas())->postJson(route('scan.gerbang.proses'), ['kode' => $a->qr_code]);
        $this->actingAs($this->petugas())->postJson(route('scan.gerbang.proses'), ['kode' => $b->qr_code]);

        $this->actingAs($this->petugas('osis'))->get(route('scan.gerbang'))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSeeInOrder(['data-kpi-tepat', 'data-kpi-terlambat']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
