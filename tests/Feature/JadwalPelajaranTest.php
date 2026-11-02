<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\Jadwal\ValidasiBentrok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 8: CRUD jadwal pelajaran + ValidasiBentrok (kelas/guru/ruangan).
 */
class JadwalPelajaranTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TahunAjaran $ta;
    private Kelas $kelasA;
    private Kelas $kelasB;
    private Mapel $mapelMtk;
    private Mapel $mapelFis;
    private Guru $guruPak;
    private Guru $guruBu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ta = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_active' => true,
        ]);

        $jurusan = Jurusan::create(['nama' => 'Teknik Komputer & Jaringan', 'singkatan' => 'TKJ']);
        $this->kelasA = Kelas::create([
            'tahun_ajaran_id' => $this->ta->id, 'jurusan_id' => $jurusan->id, 'nama_kelas' => 'X TKJ 1',
        ]);
        $this->kelasB = Kelas::create([
            'tahun_ajaran_id' => $this->ta->id, 'jurusan_id' => $jurusan->id, 'nama_kelas' => 'X TKJ 2',
        ]);

        $this->mapelMtk = Mapel::create(['kode_mapel' => 'MTK', 'nama_mapel' => 'Matematika']);
        $this->mapelFis = Mapel::create(['kode_mapel' => 'FIS', 'nama_mapel' => 'Fisika']);

        $this->guruPak = $this->buatGuru('Pak Ahmad', '1980010101');
        $this->guruBu = $this->buatGuru('Bu Sari', '1982020202');

        $this->actingAs($this->admin);
    }

    private function buatGuru(string $nama, string $nip): Guru
    {
        $user = User::factory()->create(['role' => 'guru', 'name' => $nama]);

        return Guru::create(['user_id' => $user->id, 'nip' => $nip]);
    }

    private function payload(array $tambahan = []): array
    {
        return array_merge([
            'tahun_ajaran_id' => $this->ta->id,
            'kelas_id' => $this->kelasA->id,
            'mapel_id' => $this->mapelMtk->id,
            'guru_id' => $this->guruPak->id,
            'hari' => 'senin',
            'jam_ke' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:45',
            'ruangan' => 'R. Teori 1',
            'semester' => 'ganjil',
        ], $tambahan);
    }

    /* ── Happy path ────────────────────────────────────────── */

    public function test_tambah_jadwal_lolos_dan_tampil_di_index(): void
    {
        $this->post(route('jadwal.store'), $this->payload())
            ->assertRedirect(route('jadwal.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('jadwal_pelajaran', [
            'kelas_id' => $this->kelasA->id,
            'guru_id' => $this->guruPak->id,
            'hari' => 'senin',
            'jam_ke' => 1,
        ]);

        $this->get(route('jadwal.index'))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Pak Ahmad')
            ->assertSee('07:00–07:45');
    }

    public function test_edit_jadwal_tidak_bentrok_dengan_dirinya_sendiri(): void
    {
        $this->post(route('jadwal.store'), $this->payload());

        $jadwal = JadwalPelajaran::firstOrFail();

        // Ubah mapel & ruangan pada slot yang sama — tetap boleh (baris sendiri diabaikan)
        $this->put(route('jadwal.update', $jadwal), $this->payload([
            'mapel_id' => $this->mapelFis->id,
            'ruangan' => 'R. Teori 9',
        ]))->assertRedirect(route('jadwal.index'))
            ->assertSessionHas('sukses');

        $this->assertSame('FIS', $jadwal->fresh()->mapel->kode_mapel);
    }

    /* ── Validasi bentrok (3 skenario wajib) ───────────────── */

    public function test_bentrok_kelas_ditolak(): void
    {
        $this->post(route('jadwal.store'), $this->payload());

        $this->post(route('jadwal.store'), $this->payload([
            'mapel_id' => $this->mapelFis->id,
            'guru_id' => $this->guruBu->id,
            'ruangan' => 'R. Teori 2',
        ]))->assertSessionHas('gagal');

        $this->assertStringContainsString('Bentrok kelas', session('gagal'));
        $this->assertSame(1, JadwalPelajaran::count());
    }

    public function test_bentrok_guru_ditolak_pada_kelas_berbeda(): void
    {
        $this->post(route('jadwal.store'), $this->payload());

        // Kelas lain, guru sama, hari & jam ke sama → guru tidak boleh dobel
        $this->post(route('jadwal.store'), $this->payload([
            'kelas_id' => $this->kelasB->id,
            'ruangan' => 'R. Teori 2',
        ]))->assertSessionHas('gagal');

        $this->assertStringContainsString('Bentrok guru', session('gagal'));
    }

    public function test_bentrok_ruangan_ditolak_pada_kelas_berbeda(): void
    {
        $this->post(route('jadwal.store'), $this->payload());

        // Kelas & guru berbeda, ruangan sama, waktu tumpang tindih → ditolak
        $this->post(route('jadwal.store'), $this->payload([
            'kelas_id' => $this->kelasB->id,
            'guru_id' => $this->guruBu->id,
            'jam_ke' => 2,
            'jam_mulai' => '07:30',
            'jam_selesai' => '08:15',
        ]))->assertSessionHas('gagal');

        $this->assertStringContainsString('Bentrok ruangan', session('gagal'));
    }

    public function test_tumpang_tindih_waktu_ditolak_meski_jam_ke_berbeda(): void
    {
        $this->post(route('jadwal.store'), $this->payload());

        // Jam ke-2 tapi mulai sebelum jadwal pertama selesai → kelas tumpang tindih
        $this->post(route('jadwal.store'), $this->payload([
            'mapel_id' => $this->mapelFis->id,
            'guru_id' => $this->guruBu->id,
            'jam_ke' => 2,
            'jam_mulai' => '07:40',
            'jam_selesai' => '08:25',
            'ruangan' => 'R. Teori 2',
        ]))->assertSessionHas('gagal');

        $this->assertStringContainsString('Bentrok kelas', session('gagal'));
    }

    public function test_guru_sama_boleh_mengajar_di_semester_lain(): void
    {
        $this->post(route('jadwal.store'), $this->payload());

        // Slot identik pada semester genap → tidak bentrok
        $this->post(route('jadwal.store'), $this->payload(['semester' => 'genap']))
            ->assertSessionHas('sukses');

        $this->assertSame(2, JadwalPelajaran::count());
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        $this->post(route('jadwal.store'), $this->payload([
            'jam_mulai' => '08:00',
            'jam_selesai' => '07:30',
        ]))->assertSessionHasErrors('jam_selesai');

        // Service juga menolak jam selesai <= jam mulai (lapis pengaman kedua)
        $pesan = (new ValidasiBentrok())->cek($this->payload([
            'jam_mulai' => '08:00',
            'jam_selesai' => '07:30',
        ]));
        $this->assertNotNull($pesan);
        $this->assertStringContainsString('Jam selesai', $pesan);
    }

    /* ── Filter, TA aktif, hak akses ───────────────────────── */

    public function test_filter_kelas_dan_semester_pada_index(): void
    {
        $this->post(route('jadwal.store'), $this->payload());
        $this->post(route('jadwal.store'), $this->payload([
            'kelas_id' => $this->kelasB->id,
            'mapel_id' => $this->mapelFis->id,
            'guru_id' => $this->guruBu->id,
            'jam_ke' => 2,
            'jam_mulai' => '08:00',
            'jam_selesai' => '08:45',
            'semester' => 'genap',
            'ruangan' => 'R. Teori 2',
        ]));

        $this->get(route('jadwal.index', ['kelas_id' => $this->kelasA->id]))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Fisika');

        $this->get(route('jadwal.index', ['semester' => 'genap']))
            ->assertOk()
            ->assertSee('Fisika')
            ->assertDontSee('Matematika');
    }

    public function test_hanya_jadwal_ta_aktif_yang_tampil(): void
    {
        $taLama = TahunAjaran::create([
            'nama' => '2025/2026', 'tanggal_mulai' => '2025-07-01', 'tanggal_selesai' => '2026-06-30',
            'is_active' => false,
        ]);
        JadwalPelajaran::create($this->payload([
            'tahun_ajaran_id' => $taLama->id,
            'mapel_id' => $this->mapelFis->id,
            'guru_id' => $this->guruBu->id,
        ]));

        $this->get(route('jadwal.index'))
            ->assertOk()
            ->assertDontSee('Fisika');
    }

    public function test_wali_kelas_bisa_melihat_tapi_tidak_mengelola(): void
    {
        $this->post(route('jadwal.store'), $this->payload());
        $jadwal = JadwalPelajaran::firstOrFail();

        $wali = User::factory()->create(['role' => 'wali_kelas']);

        $this->actingAs($wali)->get(route('jadwal.index'))->assertOk();

        $this->actingAs($wali)->get(route('jadwal.create'))->assertForbidden();
        $this->actingAs($wali)->post(route('jadwal.store'), $this->payload())->assertForbidden();
        // Binding model ter-resolve sebelum middleware role — baris harus ada agar bukan 404
        $this->actingAs($wali)->delete(route('jadwal.destroy', $jadwal))->assertForbidden();
        $this->assertDatabaseHas('jadwal_pelajaran', ['id' => $jadwal->id, 'deleted_at' => null]);
    }

    public function test_guru_lain_tidak_bisa_akses_jadwal(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('jadwal.index'))->assertForbidden();
    }
}
