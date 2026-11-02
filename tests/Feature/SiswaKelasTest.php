<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 7: CRUD kelas + assign siswa via pivot + cetak QR kartu pelajar.
 */
class SiswaKelasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function ta(bool $aktif = true): TahunAjaran
    {
        $urut = TahunAjaran::withTrashed()->count() + 1;

        return TahunAjaran::create([
            'nama' => "20{$urut}/20" . ($urut + 1),
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_active' => $aktif,
        ]);
    }

    private function jurusan(): Jurusan
    {
        return Jurusan::create(['nama' => 'Teknik Komputer Jaringan', 'singkatan' => 'TKJ']);
    }

    private function kelas(TahunAjaran $ta, Jurusan $j, string $nama = 'X TKJ 1'): Kelas
    {
        return Kelas::create([
            'tahun_ajaran_id' => $ta->id,
            'jurusan_id' => $j->id,
            'nama_kelas' => $nama,
        ]);
    }

    private function siswa(string $nisn = '0081234567', string $nis = '2026.001'): Siswa
    {
        return Siswa::create([
            'nisn' => $nisn,
            'nis' => $nis,
            'nama' => 'Muhammad Rizky Pratama',
            'jenis_kelamin' => 'laki-laki',
        ]);
    }

    private function daftarkan(Siswa $siswa, Kelas $kelas, TahunAjaran $ta): SiswaKelas
    {
        return SiswaKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $ta->id,
        ]);
    }

    /* ── Siswa & QR ────────────────────────────────────────── */

    public function test_tambah_siswa_membuat_qr_dan_masuk_kelas(): void
    {
        $ta = $this->ta();
        $kelas = $this->kelas($ta, $this->jurusan());

        $this->actingAs($this->admin())->post(route('master.siswa.store'), [
            'nisn' => '0081234567',
            'nis' => '2026.001',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'laki-laki',
            'kelas_id' => $kelas->id,
        ])->assertRedirect(route('master.siswa'));

        $siswa = Siswa::where('nisn', '0081234567')->firstOrFail();
        $this->assertSame("SISWA-{$siswa->id}-0081234567", $siswa->qr_code);
        $this->assertTrue($siswa->kelas_aktif->is($kelas));
    }

    public function test_nisn_dan_nis_harus_unik(): void
    {
        $admin = $this->admin();
        $this->siswa(); // NISN 0081234567, NIS 2026.001

        $this->actingAs($admin)->post(route('master.siswa.store'), [
            'nisn' => '0081234567', 'nis' => '2026.999', 'nama' => 'Lain',
            'jenis_kelamin' => 'perempuan',
        ])->assertSessionHasErrors('nisn');

        $this->actingAs($admin)->post(route('master.siswa.store'), [
            'nisn' => '0099999999', 'nis' => '2026.001', 'nama' => 'Lain',
            'jenis_kelamin' => 'perempuan',
        ])->assertSessionHasErrors('nis');
    }

    public function test_qr_code_unik_antar_siswa(): void
    {
        $a = $this->siswa('0081234567', '2026.001');
        $b = Siswa::create([
            'nisn' => '0078129034', 'nis' => '2026.012', 'nama' => 'Anindya Larasati',
            'jenis_kelamin' => 'perempuan',
        ]);

        $this->assertNotSame($a->fresh()->qr_code, $b->fresh()->qr_code);
    }

    /* ── Kelas & keanggotaan (pivot) ───────────────────────── */

    public function test_kelas_unik_per_tahun_ajaran(): void
    {
        $admin = $this->admin();
        $ta1 = $this->ta();
        $ta2 = $this->ta(false);
        $jurusan = $this->jurusan();

        $this->actingAs($admin)->post(route('master.kelas.store'), [
            'tahun_ajaran_id' => $ta1->id, 'jurusan_id' => $jurusan->id, 'nama_kelas' => 'X TKJ 1',
        ])->assertRedirect(route('master.kelas.anggota', Kelas::first()));

        // Nama sama di TA sama → ditolak
        $this->actingAs($admin)->post(route('master.kelas.store'), [
            'tahun_ajaran_id' => $ta1->id, 'jurusan_id' => $jurusan->id, 'nama_kelas' => 'X TKJ 1',
        ])->assertSessionHasErrors('nama_kelas');

        // Nama sama di TA lain → boleh
        $this->actingAs($admin)->post(route('master.kelas.store'), [
            'tahun_ajaran_id' => $ta2->id, 'jurusan_id' => $jurusan->id, 'nama_kelas' => 'X TKJ 1',
        ])->assertRedirect();
        $this->assertSame(2, Kelas::where('nama_kelas', 'X TKJ 1')->count());
    }

    public function test_pindah_antar_kelas_riwayat_ta_lama_terjaga(): void
    {
        $admin = $this->admin();
        $taAktif = $this->ta();
        $taLama = $this->ta(false);
        $jurusan = $this->jurusan();

        $kelasA = $this->kelas($taAktif, $jurusan, 'X TKJ 1');
        $kelasB = $this->kelas($taAktif, $jurusan, 'X TKJ 2');
        $kelasTaLama = $this->kelas($taLama, $jurusan, 'X TKJ 1');

        $siswa = $this->siswa();
        $this->daftarkan($siswa, $kelasA, $taAktif);
        $this->daftarkan($siswa, $kelasTaLama, $taLama);

        $pivot = SiswaKelas::where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $taAktif->id)
            ->firstOrFail();

        $this->actingAs($admin)->put(route('master.kelas.anggota.pindah', $pivot), [
            'kelas_id' => $kelasB->id,
        ])->assertSessionHas('sukses');

        // TA berjalan pindah; riwayat TA lama tidak tersentuh; jumlah riwayat tetap 2
        $this->assertTrue($siswa->fresh()->kelas_aktif->is($kelasB));
        $this->assertTrue($siswa->fresh()->kelasDiTa($taLama->id)->is($kelasTaLama));
        $this->assertSame(2, $siswa->riwayatKelas()->count());
    }

    public function test_siswa_tidak_bisa_masuk_dua_kelas_dalam_satu_ta(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $jurusan = $this->jurusan();
        $kelasA = $this->kelas($ta, $jurusan, 'X TKJ 1');
        $kelasB = $this->kelas($ta, $jurusan, 'X TKJ 2');

        $siswa = $this->siswa();
        $this->daftarkan($siswa, $kelasA, $ta);

        $this->actingAs($admin)->post(route('master.kelas.anggota.tambah', $kelasB), [
            'siswa_id' => $siswa->id,
        ])->assertSessionHas('gagal');

        $this->assertTrue($siswa->fresh()->kelas_aktif->is($kelasA));
    }

    public function test_tambah_anggota_mengaktifkan_kembali_baris_lama(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $jurusan = $this->jurusan();
        $kelasA = $this->kelas($ta, $jurusan, 'X TKJ 1');
        $kelasB = $this->kelas($ta, $jurusan, 'X TKJ 2');

        $siswa = $this->siswa();
        $pivot = $this->daftarkan($siswa, $kelasA, $ta);
        $pivot->update(['is_active' => false]); // dikeluarkan

        $this->actingAs($admin)->post(route('master.kelas.anggota.tambah', $kelasB), [
            'siswa_id' => $siswa->id,
        ])->assertSessionHas('sukses');

        $this->assertTrue($siswa->fresh()->kelas_aktif->is($kelasB));
        $this->assertSame(1, $siswa->riwayatKelas()->count(), 'Tidak membuat baris riwayat baru');
    }

    public function test_keluarkan_anggota_menonaktifkan_tanpa_menghapus_riwayat(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $kelas = $this->kelas($ta, $this->jurusan());
        $siswa = $this->siswa();
        $pivot = $this->daftarkan($siswa, $kelas, $ta);

        $this->actingAs($admin)
            ->delete(route('master.kelas.anggota.keluarkan', $pivot))
            ->assertSessionHas('sukses');

        $this->assertFalse($pivot->fresh()->is_active);
        $this->assertNull($siswa->fresh()->kelas_aktif);
        $this->assertSame(1, $siswa->riwayatKelas()->count());

        // Siswa yang barusan dikeluarkan muncul kembali di opsi "Tambah Anggota"
        // (sekaligus menangkap regresi Blade :options dengan quote bersarang)
        $this->actingAs($admin)->get(route('master.kelas.anggota', $kelas))
            ->assertOk()
            ->assertSee('0081234567')
            ->assertDontSee('nama} · ');
    }

    public function test_hapus_kelas_diblokir_selama_masih_ada_anggota_atau_jadwal(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $kelas = $this->kelas($ta, $this->jurusan());
        $siswa = $this->siswa();
        $pivot = $this->daftarkan($siswa, $kelas, $ta);

        $this->actingAs($admin)
            ->delete(route('master.kelas.destroy', $kelas))
            ->assertSessionHas('gagal');
        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'deleted_at' => null]);

        $pivot->update(['is_active' => false]);
        $this->actingAs($admin)
            ->delete(route('master.kelas.destroy', $kelas))
            ->assertSessionHas('sukses');
        $this->assertSoftDeleted('kelas', ['id' => $kelas->id]);
    }

    /* ── Halaman & pencarian ───────────────────────────────── */

    public function test_halaman_master_siswa_dan_kelas_bisa_diakses_admin(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $kelas = $this->kelas($ta, $this->jurusan());

        foreach ([
            'master.siswa', 'master.siswa.create',
            'master.kelas', 'master.kelas.create',
            'master.kelas.anggota',
        ] as $rute) {
            $this->actingAs($admin)
                ->get(route($rute, $rute === 'master.kelas.anggota' ? $kelas : []))
                ->assertOk();
        }

        $siswa = $this->siswa();
        $this->actingAs($admin)->get(route('master.siswa.edit', $siswa))->assertOk();
    }

    public function test_pencarian_siswa_berdasarkan_nisn_atau_nama(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $kelas = $this->kelas($ta, $this->jurusan());

        $rizky = $this->siswa();
        $this->daftarkan($rizky, $kelas, $ta);
        $anindya = Siswa::create([
            'nisn' => '0078129034', 'nis' => '2026.012', 'nama' => 'Anindya Larasati Putri',
            'jenis_kelamin' => 'perempuan',
        ]);
        $this->daftarkan($anindya, $kelas, $ta);

        $this->actingAs($admin)->get(route('master.siswa', ['q' => 'Rizky']))
            ->assertSee('Muhammad Rizky Pratama')
            ->assertDontSee('Anindya Larasati');

        $this->actingAs($admin)->get(route('master.siswa', ['q' => '0078129034']))
            ->assertSee('Anindya Larasati Putri')
            ->assertDontSee('Muhammad Rizky');

        $this->actingAs($admin)->get(route('master.siswa', ['kelas_id' => $kelas->id]))
            ->assertSee('Muhammad Rizky Pratama')
            ->assertSee('Anindya Larasati Putri');
    }

    /* ── Cetak kartu QR ────────────────────────────────────── */

    public function test_cetak_kartu_kelas_menghasilkan_pdf_berisi_qr(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $kelas = $this->kelas($ta, $this->jurusan());
        $siswa = $this->siswa();
        $this->daftarkan($siswa, $kelas, $ta);

        $resp = $this->actingAs($admin)->get(route('master.kelas.kartu', $kelas));

        $resp->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $resp->getContent());
    }

    public function test_cetak_kartu_per_siswa_menghasilkan_pdf(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswa();

        $resp = $this->actingAs($admin)->get(route('master.siswa.kartu', $siswa));

        $resp->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $resp->getContent());
    }

    public function test_kartu_kelas_kosong_ditolak_dengan_pesan(): void
    {
        $admin = $this->admin();
        $ta = $this->ta();
        $kelas = $this->kelas($ta, $this->jurusan());

        $this->actingAs($admin)
            ->from(route('master.kelas'))
            ->get(route('master.kelas.kartu', $kelas))
            ->assertRedirect(route('master.kelas'))
            ->assertSessionHas('gagal');
    }

    /* ── Hak akses ─────────────────────────────────────────── */

    public function test_hanya_admin_bisa_kelola_kelas_dan_siswa(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('master.siswa'))->assertForbidden();
        $this->actingAs($guru)->get(route('master.kelas'))->assertForbidden();
        $this->actingAs($guru)->post(route('master.siswa.store'), [
            'nisn' => '1', 'nis' => '1', 'nama' => 'X', 'jenis_kelamin' => 'laki-laki',
        ])->assertForbidden();
    }
}
