<?php

namespace Tests\Feature;

use App\Enums\Hari;
use App\Models\HariLibur;
use App\Models\PengaturanHari;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\PengaturanHariSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 9: pengaturan jam per hari (7 baris + toggle libur rutin)
 * dan CRUD hari libur rentang.
 */
class PengaturanHariTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TahunAjaran $ta;

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

        $this->actingAs($this->admin);
    }

    /** Payload 7 hari lengkap dari default, bisa ditimpa per hari. */
    private function payloadSimpan(array $ubah = []): array
    {
        $hari = [];
        foreach (Hari::semua() as $h) {
            $hari[$h->value] = PengaturanHari::defaultUntuk($h);
        }

        foreach ($ubah as $kunci => $isi) {
            $hari[$kunci] = array_merge($hari[$kunci], $isi);
        }

        return ['hari' => $hari];
    }

    private function pengaturan(string $hari): PengaturanHari
    {
        return PengaturanHari::where('hari', $hari)->firstOrFail();
    }

    /* ── Halaman & default ───────────────────────────────────── */

    public function test_halaman_membuat_7_baris_dengan_default_benar(): void
    {
        $this->get(route('pengaturan.hari'))
            ->assertOk()
            ->assertSee('Pengaturan Jam Presensi')
            ->assertSee('Simpan Perubahan');

        $this->assertDatabaseCount('pengaturan_hari', 7);

        $this->assertSame('masuk', $this->pengaturan('senin')->status_hari);
        $this->assertSame('07:00', $this->pengaturan('senin')->batas_tepat_waktu->format('H:i'));
        $this->assertSame('libur', $this->pengaturan('sabtu')->status_hari);
        $this->assertNull($this->pengaturan('minggu')->batas_tepat_waktu);
    }

    public function test_seeder_default_idempoten(): void
    {
        $this->seed(PengaturanHariSeeder::class);
        $this->seed(PengaturanHariSeeder::class);

        $this->assertDatabaseCount('pengaturan_hari', 7);
        $this->assertSame('masuk', $this->pengaturan('jumat')->status_hari);
        $this->assertSame('libur', $this->pengaturan('sabtu')->status_hari);
    }

    /* ── Simpan per hari ─────────────────────────────────────── */

    public function test_simpan_jumat_jadi_hari_pendek(): void
    {
        $this->post(route('pengaturan.hari.simpan'), $this->payloadSimpan([
            'jumat' => [
                'jam_auto_alpa' => '07:30',
                'mulai_scan_pulang' => '11:30',
                'akhir_scan_pulang' => '12:45',
                'keterangan' => 'Jumat — jam pelajaran pendek',
            ],
        ]))->assertRedirect(route('pengaturan.hari'))
            ->assertSessionHas('sukses');

        $jumat = $this->pengaturan('jumat');
        $this->assertSame('07:30', $jumat->jam_auto_alpa->format('H:i'));
        $this->assertSame('11:30', $jumat->mulai_scan_pulang->format('H:i'));
        $this->assertSame('12:45', $jumat->akhir_scan_pulang->format('H:i'));
        $this->assertSame('Jumat — jam pelajaran pendek', $jumat->keterangan);

        // Hari lain tidak ikut berubah
        $this->assertSame('08:00', $this->pengaturan('senin')->jam_auto_alpa->format('H:i'));
    }

    public function test_toggle_status_hari_libur_tersimpan(): void
    {
        $this->post(route('pengaturan.hari.simpan'), $this->payloadSimpan([
            'kamis' => ['status_hari' => 'libur'],
        ]))->assertRedirect(route('pengaturan.hari'));

        $this->assertSame('libur', $this->pengaturan('kamis')->status_hari);
        $this->assertTrue($this->pengaturan('kamis')->libur());
        $this->assertFalse($this->pengaturan('kamis')->siapAbsensi());
    }

    /* ── Validasi urutan waktu ───────────────────────────────── */

    public function test_urutan_waktu_yang_salah_ditolak(): void
    {
        PengaturanHari::kembalikanDefault(); // baris harus ada dulu (biasanya dibuat halaman index)

        $this->post(route('pengaturan.hari.simpan'), $this->payloadSimpan([
            'jumat' => ['batas_tepat_waktu' => '06:30'], // sebelum buka 06:45
        ]))->assertSessionHas('gagal');

        $this->assertStringContainsString('berurutan', session('gagal'));

        // Tidak ada yang tersimpan
        $this->assertSame('07:00', $this->pengaturan('jumat')->batas_tepat_waktu->format('H:i'));
    }

    public function test_akhir_pulang_harus_setelah_mulai_ditolak(): void
    {
        PengaturanHari::kembalikanDefault(); // baris harus ada dulu (biasanya dibuat halaman index)

        $this->post(route('pengaturan.hari.simpan'), $this->payloadSimpan([
            'selasa' => ['mulai_scan_pulang' => '14:00', 'akhir_scan_pulang' => '13:00'],
        ]))->assertSessionHas('gagal');

        $this->assertStringContainsString('scan pulang', session('gagal'));
        $this->assertSame('15:30', $this->pengaturan('selasa')->akhir_scan_pulang->format('H:i'));
    }

    /* ── Reset default ───────────────────────────────────────── */

    public function test_reset_default_mengembalikan_bawaan(): void
    {
        $this->post(route('pengaturan.hari.simpan'), $this->payloadSimpan([
            'jumat' => ['jam_auto_alpa' => '07:30', 'status_hari' => 'libur'],
        ]));

        $this->post(route('pengaturan.hari.reset'))
            ->assertRedirect(route('pengaturan.hari'))
            ->assertSessionHas('sukses');

        $jumat = $this->pengaturan('jumat');
        $this->assertSame('08:00', $jumat->jam_auto_alpa->format('H:i'));
        $this->assertSame('masuk', $jumat->status_hari);
        $this->assertSame('libur', $this->pengaturan('sabtu')->status_hari);
    }

    /* ── Hari libur rentang ──────────────────────────────────── */

    public function test_crud_hari_libur_rentang(): void
    {
        // Halaman form create ter-render (menangkap view hilang / error blade)
        $this->get(route('pengaturan.libur.create'))
            ->assertOk()
            ->assertSee('Nama Libur / Agenda');

        // Simpan rentang 3 hari
        $this->post(route('pengaturan.libur.store'), [
            'tahun_ajaran_id' => $this->ta->id,
            'tanggal_mulai' => '2026-12-25',
            'tanggal_selesai' => '2026-12-27',
            'keterangan' => 'Libur Natal',
            'status_masuk' => 'tidak_masuk',
        ])->assertRedirect(route('pengaturan.libur'))
            ->assertSessionHas('sukses');

        $libur = HariLibur::firstOrFail();
        $this->assertSame('Libur Natal', $libur->keterangan);
        $this->assertSame('2026-12-27', $libur->tanggal_selesai->format('Y-m-d'));

        // Index menampilkan + tombol aksi
        $this->get(route('pengaturan.libur'))
            ->assertOk()
            ->assertSee('Libur Natal')
            ->assertSee('25 Des 2026');

        // Halaman edit ter-render
        $this->get(route('pengaturan.libur.edit', $libur))
            ->assertOk()
            ->assertSee('Libur Natal');

        // Ubah jadi status kegiatan
        $this->put(route('pengaturan.libur.update', $libur), [
            'tahun_ajaran_id' => $this->ta->id,
            'tanggal_mulai' => '2026-12-25',
            'tanggal_selesai' => null,
            'keterangan' => 'Uji Kompetensi Keahlian',
            'status_masuk' => 'masuk',
        ])->assertRedirect(route('pengaturan.libur'));

        $libur->refresh();
        $this->assertSame('Uji Kompetensi Keahlian', $libur->keterangan);
        $this->assertSame('masuk', $libur->status_masuk);
        $this->assertNull($libur->tanggal_selesai); // satu hari

        // Hapus
        $this->delete(route('pengaturan.libur.destroy', $libur))
            ->assertRedirect(route('pengaturan.libur'));
        $this->assertDatabaseCount('hari_libur', 0);
    }

    public function test_validasi_tanggal_libur(): void
    {
        $this->post(route('pengaturan.libur.store'), [
            'tahun_ajaran_id' => $this->ta->id,
            'tanggal_mulai' => '2026-12-27',
            'tanggal_selesai' => '2026-12-25',
            'keterangan' => 'Salah Ketik',
            'status_masuk' => 'tidak_masuk',
        ])->assertSessionHasErrors('tanggal_selesai');

        $this->post(route('pengaturan.libur.store'), [
            'tahun_ajaran_id' => $this->ta->id,
            'tanggal_mulai' => '2026-12-27',
            'keterangan' => '',
            'status_masuk' => 'tidak_masuk',
        ])->assertSessionHasErrors('keterangan');

        $this->assertDatabaseCount('hari_libur', 0);
    }

    public function test_index_libur_hanya_tampil_ta_aktif(): void
    {
        $taLama = TahunAjaran::create([
            'nama' => '2025/2026',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'is_active' => false,
        ]);
        HariLibur::create([
            'tahun_ajaran_id' => $taLama->id,
            'tanggal_mulai' => '2025-12-25',
            'keterangan' => 'Libur Natal Lama',
            'status_masuk' => 'tidak_masuk',
        ]);
        HariLibur::create([
            'tahun_ajaran_id' => $this->ta->id,
            'tanggal_mulai' => '2026-12-25',
            'keterangan' => 'Libur Natal Baru',
            'status_masuk' => 'tidak_masuk',
        ]);

        $this->get(route('pengaturan.libur'))
            ->assertOk()
            ->assertSee('Libur Natal Baru')
            ->assertDontSee('Libur Natal Lama');
    }

    /* ── Hak akses ───────────────────────────────────────────── */

    public function test_hanya_admin_yang_bisa_mengelola(): void
    {
        $libur = HariLibur::create([
            'tahun_ajaran_id' => $this->ta->id,
            'tanggal_mulai' => '2026-08-17',
            'keterangan' => 'Proklamasi',
            'status_masuk' => 'tidak_masuk',
        ]);

        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('pengaturan.hari'))->assertForbidden();
        $this->actingAs($guru)->post(route('pengaturan.hari.simpan'), $this->payloadSimpan())->assertForbidden();
        $this->actingAs($guru)->get(route('pengaturan.libur'))->assertForbidden();
        $this->actingAs($guru)->get(route('pengaturan.libur.create'))->assertForbidden();
        // Binding model ter-resolve sebelum middleware role — baris harus ada agar 403, bukan 404
        $this->actingAs($guru)->delete(route('pengaturan.libur.destroy', $libur))->assertForbidden();

        $this->assertDatabaseHas('hari_libur', ['id' => $libur->id]);
        $this->assertSame(0, PengaturanHari::count());
    }
}
