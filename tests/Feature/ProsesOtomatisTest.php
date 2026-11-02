<?php

namespace Tests\Feature;

use App\Models\AbsensiGerbang;
use App\Models\AbsensiPulang;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PengaturanHari;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 12 — Job otomatis `absensi:proses-otomatis` (desain §7.5):
 * pre-create pulang, auto-alpa anti-join, auto-kabur. Default Senin
 * (2 Nov 2026): buka 06:45 · alpa 08:00 · pulang 14:00–15:30.
 */
class ProsesOtomatisTest extends TestCase
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

    private function siswa(string $nisn, bool $aktif = true, bool $anggota = true): Siswa
    {
        $siswa = Siswa::create([
            'nisn' => $nisn,
            'nis' => '2026.'.random_int(100, 999),
            'nama' => 'Siswa '.$nisn,
            'jenis_kelamin' => 'laki-laki',
            'active' => $aktif,
        ]);

        if ($anggota) {
            SiswaKelas::create([
                'siswa_id' => $siswa->id, 'kelas_id' => $this->kelas->id, 'tahun_ajaran_id' => $this->ta->id,
            ]);
        }

        return $siswa;
    }

    private function gerbang(Siswa $siswa, string $status): AbsensiGerbang
    {
        return AbsensiGerbang::create([
            'siswa_id' => $siswa->id, 'tanggal' => Carbon::today(),
            'waktu_scan' => $status === 'alpa' ? null : Carbon::now(),
            'status' => $status, 'metode' => $status === 'alpa' ? 'auto' : 'qr',
        ]);
    }

    private function jam(string $waktu): void
    {
        Carbon::setTestNow(Carbon::parse($waktu));
    }

    private function jalankan(): void
    {
        $this->artisan('absensi:proses-otomatis')->assertExitCode(0);
    }

    /* ── Pre-create absensi_pulang ───────────────────────────── */

    public function test_precreate_membuat_baris_belum_absen_untuk_siswa_aktif(): void
    {
        $this->jam('2026-11-02 06:45');
        $siswa = $this->siswa('0081111111');

        $this->jalankan();

        $this->assertDatabaseHas('absensi_pulang', [
            'siswa_id' => $siswa->id, 'status' => 'belum_absen', 'metode' => 'auto',
        ]);
    }

    public function test_precreate_melewati_siswa_nonaktif(): void
    {
        $this->jam('2026-11-02 06:45');
        $aktif = $this->siswa('0081111111');
        $this->siswa('0082222222', aktif: false);

        $this->jalankan();

        $this->assertDatabaseCount('absensi_pulang', 1);
        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $aktif->id]);
    }

    public function test_precreate_melewati_siswa_bukan_anggota_ta_aktif(): void
    {
        $this->jam('2026-11-02 06:45');
        $this->siswa('0081111111', anggota: false); // tanpa pivot
        $this->siswa('0082222222'); // lalu dikeluarkan
        SiswaKelas::query()->update(['is_active' => false]);

        $this->jalankan();

        $this->assertDatabaseCount('absensi_pulang', 0);
    }

    public function test_precreate_idempoten_dijalan_dua_kali(): void
    {
        $this->jam('2026-11-02 06:45');
        $this->siswa('0081111111');

        $this->jalankan();
        $this->jalankan();

        $this->assertDatabaseCount('absensi_pulang', 1);
    }

    public function test_command_sebelum_jam_buka_belum_precreate(): void
    {
        $this->jam('2026-11-02 06:00'); // buka 06:45
        $this->siswa('0081111111');

        $this->jalankan();

        $this->assertDatabaseCount('absensi_pulang', 0);
    }

    /* ── Auto-alpa ───────────────────────────────────────────── */

    public function test_auto_alpa_memberi_alpa_pada_siswa_tanpa_gerbang(): void
    {
        $this->jam('2026-11-02 08:05'); // jam alpa 08:00 lewat
        $hadir = $this->siswa('0081111111');
        $this->gerbang($hadir, 'hadir_tepat_waktu');
        $bolos = $this->siswa('0082222222');

        $this->jalankan();

        $this->assertDatabaseHas('absensi_gerbang', [
            'siswa_id' => $bolos->id, 'status' => 'alpa', 'metode' => 'auto', 'waktu_scan' => null,
        ]);
        // Siswa hadir tidak tersentuh
        $this->assertSame('hadir_tepat_waktu', AbsensiGerbang::where('siswa_id', $hadir->id)->first()->status->value);
    }

    public function test_auto_alpa_melewati_siswa_izin_dan_sakit(): void
    {
        $this->jam('2026-11-02 08:05');
        $izin = $this->siswa('0081111111');
        $this->gerbang($izin, 'izin');
        $sakit = $this->siswa('0082222222');
        $this->gerbang($sakit, 'sakit');

        $this->jalankan();

        $this->assertSame(0, AbsensiGerbang::where('status', 'alpa')->count());
        $this->assertSame('izin', AbsensiGerbang::where('siswa_id', $izin->id)->first()->status->value);
        $this->assertSame('sakit', AbsensiGerbang::where('siswa_id', $sakit->id)->first()->status->value);
    }

    public function test_auto_alpa_idempoten_dijalan_dua_kali(): void
    {
        $this->jam('2026-11-02 08:05');
        $this->siswa('0081111111');

        $this->jalankan();
        $this->jalankan();

        $this->assertDatabaseCount('absensi_gerbang', 1);
    }

    public function test_auto_alpa_sebelum_jam_alpa_nol_aksi(): void
    {
        $this->jam('2026-11-02 07:10'); // alpa 08:00 belum tercapai
        $this->siswa('0081111111');

        $this->jalankan();

        $this->assertDatabaseCount('absensi_gerbang', 0);
    }

    /* ── Auto-kabur ──────────────────────────────────────────── */

    public function test_auto_kabur_hanya_mengubah_siswa_hadir_pagi(): void
    {
        $this->jam('2026-11-02 15:31'); // akhir pulang 15:30 + 1 menit
        $hadir = $this->siswa('0081111111');
        $this->gerbang($hadir, 'terlambat');
        $alpa = $this->siswa('0082222222');
        $this->gerbang($alpa, 'alpa');

        $this->jalankan();

        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $hadir->id, 'status' => 'kabur']);
        // Siswa alpa: baris pulang dibiarkan belum_absen (rekap tampil '-')
        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $alpa->id, 'status' => 'belum_absen']);
    }

    public function test_auto_kabur_melewati_siswa_sudah_pulang(): void
    {
        $this->jam('2026-11-02 15:31');
        $siswa = $this->siswa('0081111111');
        $this->gerbang($siswa, 'hadir_tepat_waktu');
        AbsensiPulang::create([
            'siswa_id' => $siswa->id, 'tanggal' => Carbon::today(),
            'waktu_scan' => Carbon::parse('2026-11-02 15:10'), 'status' => 'pulang', 'metode' => 'qr',
        ]);

        $this->jalankan();

        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'status' => 'pulang']);
    }

    public function test_auto_kabur_melewati_siswa_izin(): void
    {
        $this->jam('2026-11-02 15:31');
        $izin = $this->siswa('0081111111');
        $this->gerbang($izin, 'izin');

        $this->jalankan();

        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $izin->id, 'status' => 'belum_absen']);
    }

    public function test_auto_kabur_idempoten_dijalan_dua_kali(): void
    {
        $this->jam('2026-11-02 15:31');
        $siswa = $this->siswa('0081111111');
        $this->gerbang($siswa, 'hadir_tepat_waktu');

        $this->jalankan();
        $this->jalankan();

        $this->assertSame(1, AbsensiPulang::where('status', 'kabur')->count());
    }

    public function test_auto_kabur_sebelum_akhir_jendela_nol_aksi(): void
    {
        $this->jam('2026-11-02 15:00'); // jendela pulang masih buka
        $siswa = $this->siswa('0081111111');
        $this->gerbang($siswa, 'hadir_tepat_waktu');

        $this->jalankan();

        $this->assertDatabaseHas('absensi_pulang', ['siswa_id' => $siswa->id, 'status' => 'belum_absen']);
    }

    /* ── Hari libur & pengaturan ─────────────────────────────── */

    public function test_hari_libur_nol_aksi_total(): void
    {
        $this->jam('2026-11-07 08:05'); // Sabtu — libur rutin
        $this->siswa('0081111111');

        $this->artisan('absensi:proses-otomatis')
            ->expectsOutputToContain('Dilewati')
            ->assertExitCode(0);

        $this->assertDatabaseCount('absensi_pulang', 0);
        $this->assertDatabaseCount('absensi_gerbang', 0);
    }

    public function test_jam_belum_dikonfigurasi_nol_aksi(): void
    {
        PengaturanHari::untuk('senin')->update([
            'buka_scan_gerbang' => null, 'batas_tepat_waktu' => null, 'jam_tutup_gerbang' => null,
            'jam_auto_alpa' => null, 'mulai_scan_pulang' => null, 'akhir_scan_pulang' => null,
        ]);
        $this->jam('2026-11-02 08:05');
        $this->siswa('0081111111');

        $this->jalankan();

        $this->assertDatabaseCount('absensi_pulang', 0);
        $this->assertDatabaseCount('absensi_gerbang', 0);
    }

    public function test_siklus_penuh_dua_kali_berturut_bertetap_sama(): void
    {
        $this->jam('2026-11-02 15:31');
        $hadir = $this->siswa('0081111111');
        $this->gerbang($hadir, 'hadir_tepat_waktu');
        $this->siswa('0082222222'); // alpa kena job

        $this->jalankan();
        $sebelum = [
            'pulang' => AbsensiPulang::count(),
            'gerbang' => AbsensiGerbang::count(),
            'kabur' => AbsensiPulang::where('status', 'kabur')->count(),
            'alpa' => AbsensiGerbang::where('status', 'alpa')->count(),
        ];

        $this->jalankan(); // kedua kalinya — semua langkah sudah dikunci cache key

        $this->assertSame($sebelum, [
            'pulang' => AbsensiPulang::count(),
            'gerbang' => AbsensiGerbang::count(),
            'kabur' => AbsensiPulang::where('status', 'kabur')->count(),
            'alpa' => AbsensiGerbang::where('status', 'alpa')->count(),
        ]);
        $this->assertSame(1, $sebelum['alpa']);
        $this->assertSame(1, $sebelum['kabur']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
