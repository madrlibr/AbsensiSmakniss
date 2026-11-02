<?php

namespace Tests\Feature;

use App\Models\AbsensiPulang;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PengaturanHari;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 13 — Scanner pulang: update baris pre-created dalam jendela,
 * aksi piket pulang_cepat + kode karcis cetak. Default Senin (2 Nov 2026):
 * jendela pulang 14:00–15:30.
 */
class ScanPulangTest extends TestCase
{
    use RefreshDatabase;

    private TahunAjaran $ta;
    private Jurusan $jurusan;
    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30', 'is_active' => true,
        ]);
        $this->jurusan = Jurusan::create(['nama' => 'TKJ', 'singkatan' => 'TKJ']);
        $this->kelas = Kelas::create([
            'tahun_ajaran_id' => $this->ta->id, 'jurusan_id' => $this->jurusan->id, 'nama_kelas' => 'X TKJ 1',
        ]);
        PengaturanHari::kembalikanDefault();
    }

    private function petugas(string $role = 'piket'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function siswa(string $nisn = '0081234567'): Siswa
    {
        $siswa = Siswa::create([
            'nisn' => $nisn,
            'nis' => '2026.'.rand(100, 999),
            'nama' => 'Muhammad Rizky Pratama',
            'jenis_kelamin' => 'laki-laki',
        ]);
        SiswaKelas::create([
            'siswa_id' => $siswa->id, 'kelas_id' => $this->kelas->id, 'tahun_ajaran_id' => $this->ta->id,
        ]);

        return $siswa;
    }

    private function barisPulang(Siswa $siswa, string $status = 'belum_absen'): AbsensiPulang
    {
        return AbsensiPulang::create([
            'siswa_id' => $siswa->id, 'tanggal' => Carbon::today(),
            'waktu_scan' => in_array($status, ['pulang', 'pulang_cepat', 'kabur'], true) ? Carbon::now() : null,
            'status' => $status, 'metode' => $status === 'belum_absen' ? 'auto' : 'qr',
        ]);
    }

    private function jam(string $waktu): void
    {
        Carbon::setTestNow(Carbon::parse($waktu));
    }

    /* ── Scan dalam jendela ──────────────────────────────────── */

    public function test_scan_dalam_jendela_mengubah_ke_pulang(): void
    {
        $this->jam('2026-11-02 14:30');
        $siswa = $this->siswa();
        $this->barisPulang($siswa);
        $osis = $this->petugas('osis');

        $res = $this->actingAs($osis)->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertOk()
            ->assertJsonPath('jenis', 'sukses')
            ->assertJsonPath('status', 'pulang')
            ->assertJsonPath('siswa.nama', 'Muhammad Rizky Pratama');

        $this->assertDatabaseHas('absensi_pulang', [
            'siswa_id' => $siswa->id, 'status' => 'pulang', 'metode' => 'qr', 'scanned_by' => $osis->id,
        ]);
        $this->assertNotNull(AbsensiPulang::first()->waktu_scan);
    }

    public function test_scan_sebelum_jendela_ditolak(): void
    {
        $this->jam('2026-11-02 13:00');
        $siswa = $this->siswa();
        $this->barisPulang($siswa);

        $res = $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Belum waktunya pulang', $res->json('pesan'));
        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'status' => 'belum_absen']);
    }

    public function test_scan_setelah_jendela_ditolak(): void
    {
        $this->jam('2026-11-02 15:31');
        $siswa = $this->siswa();
        $this->barisPulang($siswa);

        $res = $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Jendela pulang berakhir', $res->json('pesan'));
    }

    public function test_input_manual_piket_dalam_jendela(): void
    {
        $this->jam('2026-11-02 15:00');
        $siswa = $this->siswa();
        $this->barisPulang($siswa);

        $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), [
            'kode' => $siswa->nisn, 'manual' => 1,
        ])->assertOk()->assertJsonPath('status', 'pulang');

        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'metode' => 'manual']);
    }

    /* ── Idempoten & baris final ─────────────────────────────── */

    public function test_scan_ganda_idempoten(): void
    {
        $this->jam('2026-11-02 14:30');
        $siswa = $this->siswa();
        $this->barisPulang($siswa);
        $piket = $this->petugas();

        $this->actingAs($piket)->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code])->assertOk();
        $kedua = $this->actingAs($piket)->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code]);

        $kedua->assertOk()
            ->assertJsonPath('jenis', 'info')
            ->assertJsonPath('pesan', 'Sudah pulang jam 14:30 (Pulang).');

        $this->assertDatabaseCount('absensi_pulang', 1);
        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'status' => 'pulang']);
    }

    public function test_siswa_kabur_tidak_bisa_diubah_scan(): void
    {
        $this->jam('2026-11-02 16:00');
        $siswa = $this->siswa();
        $this->barisPulang($siswa, 'kabur');

        $res = $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('koreksi', $res->json('pesan'));
        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'status' => 'kabur']);
    }

    public function test_tanpa_baris_precreate_ditolak(): void
    {
        $this->jam('2026-11-02 14:30');
        $siswa = $this->siswa(); // baris pulang sengaja tidak dibuat

        $res = $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('job harian', $res->json('pesan'));
    }

    /* ── Pulang cepat + karcis ───────────────────────────────── */

    public function test_pulang_cepat_piket_kapan_pun_mendapat_karcis(): void
    {
        $this->jam('2026-11-02 10:00'); // jauh sebelum jendela — tetap boleh
        $siswa = $this->siswa();
        $this->barisPulang($siswa);

        $res = $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), [
            'kode' => $siswa->nisn,
            'pulang_cepat' => 1,
            'keterangan' => 'Izin pulang cepat — sakit, dijemput wali kelas.',
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'pulang_cepat')
            ->assertJsonPath('karcis.kode', 'KC-261102-0081234567')
            ->assertJsonStructure(['karcis' => ['kode', 'url']]);

        $this->assertDatabaseHas('absensi_pulang', [
            'siswa_id' => $siswa->id,
            'status' => 'pulang_cepat',
            'metode' => 'manual',
            'kode_karcis' => 'KC-261102-0081234567',
            'keterangan' => 'Izin pulang cepat — sakit, dijemput wali kelas.',
        ]);
    }

    public function test_pulang_cepat_tanpa_keterangan_ditolak(): void
    {
        $this->jam('2026-11-02 10:00');
        $siswa = $this->siswa();
        $this->barisPulang($siswa);

        $res = $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), [
            'kode' => $siswa->nisn, 'pulang_cepat' => 1,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Keterangan wajib', $res->json('pesan'));
        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'status' => 'belum_absen']);
    }

    public function test_pulang_cepat_ditolak_bagianosis(): void
    {
        $this->jam('2026-11-02 14:30');
        $siswa = $this->siswa();
        $this->barisPulang($siswa);

        $res = $this->actingAs($this->petugas('osis'))->postJson(route('scan.pulang.proses'), [
            'kode' => $siswa->nisn, 'pulang_cepat' => 1, 'keterangan' => 'Dicoba OSIS.',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('petugas piket/admin', $res->json('pesan'));
    }

    /* ── Hari libur ──────────────────────────────────────────── */

    public function test_scan_hari_libur_ditolak_dengan_pesan_libur(): void
    {
        $this->jam('2026-11-07 14:30'); // Sabtu
        $siswa = $this->siswa();
        $this->barisPulang($siswa);

        $res = $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), ['kode' => $siswa->qr_code]);

        $res->assertStatus(422);
        $this->assertStringContainsString('libur sesuai pengaturan', $res->json('pesan'));
        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'status' => 'belum_absen']);
    }

    /* ── Halaman & karcis ────────────────────────────────────── */

    public function test_halaman_scan_pulang_untuk_piket_dan_osis(): void
    {
        $this->actingAs($this->petugas('piket'))->get(route('scan.pulang'))
            ->assertOk()
            ->assertSee('Scanner Pulang')
            ->assertSee('Log Scan Terkini')
            ->assertSee('Karcis Pulang');

        $this->actingAs($this->petugas('osis'))->get(route('scan.pulang'))->assertOk();
        $this->actingAs($this->petugas('guru'))->get(route('scan.pulang'))->assertForbidden();
    }

    public function test_halaman_menampilkan_kpi_dan_log(): void
    {
        $this->jam('2026-11-02 14:35');
        $a = $this->siswa('0081111111');
        $this->barisPulang($a);
        $b = $this->siswa('0082222222');
        $this->barisPulang($b);
        $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), ['kode' => $a->qr_code]);

        $this->actingAs($this->petugas('osis'))->get(route('scan.pulang'))
            ->assertOk()
            ->assertSeeInOrder(['data-kpi-tepat', 'data-kpi-terlambat', 'data-kpi-total'])
            ->assertSee('Muhammad Rizky Pratama');
    }

    public function test_halaman_karcis_menampilkan_kode_dan_siswa(): void
    {
        $this->jam('2026-11-02 10:00');
        $siswa = $this->siswa();
        $baris = $this->barisPulang($siswa);
        $this->actingAs($this->petugas())->postJson(route('scan.pulang.proses'), [
            'kode' => $siswa->nisn, 'pulang_cepat' => 1, 'keterangan' => 'Sakit dijemput ortu.',
        ]);
        $baris->refresh();

        $this->actingAs($this->petugas())->get(route('scan.pulang.karcis', $baris))
            ->assertOk()
            ->assertSee('KC-261102-0081234567')
            ->assertSee('Muhammad Rizky Pratama')
            ->assertSee('Karcis Pulang Cepat')
            ->assertSee('Sakit dijemput ortu.');

        $this->actingAs($this->petugas())->get(route('scan.pulang.karcis', 9999))->assertNotFound();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
