<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 6: CRUD tahun ajaran (switch aktif), jurusan, mapel, guru — admin only.
 */
class MasterCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /* ── Akses halaman ─────────────────────────────────────── */

    public function test_seluruh_halaman_crud_master_bisa_diakses_admin(): void
    {
        foreach ([
            'master.tahun-ajaran', 'master.tahun-ajaran.create',
            'master.jurusan', 'master.jurusan.create',
            'master.mapel', 'master.mapel.create',
            'master.guru', 'master.guru.create',
        ] as $rute) {
            $this->actingAs($this->admin())->get(route($rute))->assertOk();
        }
    }

    public function test_non_admin_ditolak_semua_crud_master(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('master.mapel.create'))->assertForbidden();
        $this->actingAs($guru)->post(route('master.mapel.store'), [
            'kode_mapel' => 'X-1', 'nama_mapel' => 'X', 'jumlah_jam_per_minggu' => 2,
        ])->assertForbidden();

        $ta = \App\Models\TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30',
        ]);
        $this->actingAs($guru)->post(route('master.tahun-ajaran.aktifkan', $ta))->assertForbidden();
    }

    /* ── Tahun Ajaran ──────────────────────────────────────── */

    public function test_tahun_ajaran_baru_dibuat_dengan_validasi(): void
    {
        $this->actingAs($this->admin())
            ->post(route('master.tahun-ajaran.store'), [
                'nama' => '2026/2027',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
            ])
            ->assertRedirect(route('master.tahun-ajaran'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('tahun_ajaran', ['nama' => '2026/2027', 'is_active' => false]);

        // Nama duplikat & tanggal selesai sebelum mulai → ditolak
        $this->actingAs($this->admin())
            ->from(route('master.tahun-ajaran.create'))
            ->post(route('master.tahun-ajaran.store'), [
                'nama' => '2026/2027',
                'tanggal_mulai' => '2027-07-01',
                'tanggal_selesai' => '2026-06-30',
            ])
            ->assertRedirect(route('master.tahun-ajaran.create'))
            ->assertSessionHasErrors(['nama', 'tanggal_selesai']);
    }

    public function test_switch_aktif_meninggalkan_satu_tahun_ajaran_aktif_saja(): void
    {
        $lama = TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30', 'is_active' => true,
        ]);
        $baru = TahunAjaran::create([
            'nama' => '2027/2028', 'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30', 'is_active' => false,
        ]);

        $this->actingAs($this->admin())
            ->post(route('master.tahun-ajaran.aktifkan', $baru))
            ->assertSessionHas('sukses');

        $this->assertFalse($lama->fresh()->is_active);
        $this->assertTrue($baru->fresh()->is_active);
        $this->assertSame(1, TahunAjaran::query()->aktif()->count());
    }

    public function test_tahun_ajaran_aktif_tidak_bisa_dihapus(): void
    {
        $aktif = TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30', 'is_active' => true,
        ]);
        $lama = TahunAjaran::create([
            'nama' => '2025/2026', 'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30', 'is_active' => false,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('master.tahun-ajaran.destroy', $aktif))
            ->assertSessionHas('gagal');
        $this->assertDatabaseHas('tahun_ajaran', ['id' => $aktif->id]);

        $this->actingAs($this->admin())
            ->delete(route('master.tahun-ajaran.destroy', $lama))
            ->assertSessionHas('sukses');
        $this->assertSoftDeleted('tahun_ajaran', ['id' => $lama->id]);
    }

    public function test_ubah_tahun_ajaran_tolak_nama_ganda(): void
    {
        $ta = TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
        ]);
        TahunAjaran::create([
            'nama' => '2025/2026', 'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
        ]);

        $this->actingAs($this->admin())
            ->put(route('master.tahun-ajaran.update', $ta), [
                'nama' => '2025/2026', // milik baris lain
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
            ])
            ->assertSessionHasErrors('nama');

        // Nama sendiri tetap boleh (diabaikan oleh aturan unik)
        $this->actingAs($this->admin())
            ->put(route('master.tahun-ajaran.update', $ta), [
                'nama' => '2026/2027',
                'tanggal_mulai' => '2026-07-15',
                'tanggal_selesai' => '2027-06-30',
            ])
            ->assertRedirect(route('master.tahun-ajaran'));

        $this->assertSame('2026-07-15', $ta->fresh()->tanggal_mulai->format('Y-m-d'));
    }

    /* ── Jurusan ───────────────────────────────────────────── */

    public function test_crud_jurusan_dengan_validasi_singkatan_unik(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('master.jurusan.store'), [
            'nama' => 'Teknik Komputer Jaringan', 'singkatan' => 'TKJ',
        ])->assertRedirect(route('master.jurusan'));
        $this->assertDatabaseHas('jurusan', ['singkatan' => 'TKJ']);

        // Singkatan duplikat → tolak
        $this->actingAs($admin)->post(route('master.jurusan.store'), [
            'nama' => 'Lain', 'singkatan' => 'TKJ',
        ])->assertSessionHasErrors('singkatan');

        $jurusan = Jurusan::first();
        $this->actingAs($admin)->put(route('master.jurusan.update', $jurusan), [
            'nama' => 'TKJ — Revisi', 'singkatan' => 'TKJ',
        ])->assertRedirect(route('master.jurusan'));

        $this->assertSame('TKJ — Revisi', $jurusan->fresh()->nama);
    }

    public function test_hapus_jurusan_yang_punya_kelas_ditolak(): void
    {
        $admin = $this->admin();
        $jurusan = Jurusan::create(['nama' => 'TKJ', 'singkatan' => 'TKJ']);
        $ta = TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30',
        ]);
        Kelas::create(['tahun_ajaran_id' => $ta->id, 'jurusan_id' => $jurusan->id, 'nama_kelas' => 'X TKJ 1']);

        $this->actingAs($admin)
            ->delete(route('master.jurusan.destroy', $jurusan))
            ->assertSessionHas('gagal');
        $this->assertDatabaseHas('jurusan', ['id' => $jurusan->id]);
    }

    /* ── Mata Pelajaran ────────────────────────────────────── */

    public function test_crud_mapel_dengan_validasi_kode_unik(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('master.mapel.store'), [
            'kode_mapel' => 'MTK-101', 'nama_mapel' => 'Matematika', 'jumlah_jam_per_minggu' => 4,
        ])->assertRedirect(route('master.mapel'));
        $this->assertDatabaseHas('mapel', ['kode_mapel' => 'MTK-101', 'jumlah_jam_per_minggu' => 4]);

        $this->actingAs($admin)->post(route('master.mapel.store'), [
            'kode_mapel' => 'MTK-101', 'nama_mapel' => 'Lain', 'jumlah_jam_per_minggu' => 2,
        ])->assertSessionHasErrors('kode_mapel');

        $mapel = Mapel::first();
        $this->actingAs($admin)->put(route('master.mapel.update', $mapel), [
            'kode_mapel' => 'MTK-101', 'nama_mapel' => 'Matematika Umum', 'jumlah_jam_per_minggu' => 3,
        ])->assertRedirect(route('master.mapel'));
        $this->assertSame('Matematika Umum', $mapel->fresh()->nama_mapel);

        $this->actingAs($admin)
            ->delete(route('master.mapel.destroy', $mapel))
            ->assertSessionHas('sukses');
        $this->assertSoftDeleted('mapel', ['id' => $mapel->id]);
    }

    public function test_mapel_yang_masih_di_jadwal_tidak_bisa_dihapus(): void
    {
        $admin = $this->admin();
        $ta = TahunAjaran::create([
            'nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30',
        ]);
        $jurusan = Jurusan::create(['nama' => 'TKJ', 'singkatan' => 'TKJ']);
        $kelas = Kelas::create(['tahun_ajaran_id' => $ta->id, 'jurusan_id' => $jurusan->id, 'nama_kelas' => 'X TKJ 1']);
        $mapel = Mapel::create(['kode_mapel' => 'MTK-101', 'nama_mapel' => 'Matematika', 'jumlah_jam_per_minggu' => 4]);
        $userGuru = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create(['user_id' => $userGuru->id, 'nip' => '198504122001011001']);

        $ta->jadwalPelajaran()->create([
            'kelas_id' => $kelas->id, 'mapel_id' => $mapel->id, 'guru_id' => $guru->id,
            'hari' => 'senin', 'jam_ke' => 1, 'jam_mulai' => '07:00', 'jam_selesai' => '07:45',
            'semester' => 'ganjil',
        ]);

        $this->actingAs($admin)
            ->delete(route('master.mapel.destroy', $mapel))
            ->assertSessionHas('gagal');
        $this->assertDatabaseHas('mapel', ['id' => $mapel->id, 'deleted_at' => null]);
    }

    /* ── Guru ──────────────────────────────────────────────── */

    public function test_tambah_guru_membuat_akun_login(): void
    {
        $mapel = Mapel::create(['kode_mapel' => 'MTK-101', 'nama_mapel' => 'Matematika', 'jumlah_jam_per_minggu' => 4]);

        $this->actingAs($this->admin())->post(route('master.guru.store'), [
            'name' => 'Budi Suryanto, S.Pd',
            'email' => 'budi@sekolah.test',
            'password' => 'rahasia123',
            'nip' => '198504122001011001',
            'telepon' => '081234567890',
            'mapel_utama_id' => $mapel->id,
        ])->assertRedirect(route('master.guru'));

        $user = User::where('email', 'budi@sekolah.test')->firstOrFail();
        $this->assertSame('guru', $user->role->value);
        $this->assertTrue($user->is_active);
        $this->assertCredentials(['email' => 'budi@sekolah.test', 'password' => 'rahasia123']);
        $this->assertDatabaseHas('guru', [
            'user_id' => $user->id, 'nip' => '198504122001011001', 'mapel_utama_id' => $mapel->id,
        ]);
    }

    public function test_nip_dan_email_guru_harus_unik(): void
    {
        $admin = $this->admin();
        $ada = User::factory()->create(['role' => 'guru', 'email' => 'lama@sekolah.test']);
        Guru::create(['user_id' => $ada->id, 'nip' => '198504122001011001']);

        // NIP sama, email baru → ditolak di NIP
        $this->actingAs($admin)->post(route('master.guru.store'), [
            'name' => 'Baru', 'email' => 'baru@sekolah.test', 'password' => 'rahasia123',
            'nip' => '198504122001011001',
        ])->assertSessionHasErrors('nip');

        // Email sama, NIP baru → ditolak di email
        $this->actingAs($admin)->post(route('master.guru.store'), [
            'name' => 'Baru', 'email' => 'lama@sekolah.test', 'password' => 'rahasia123',
            'nip' => '199001012015011002',
        ])->assertSessionHasErrors('email');
    }

    public function test_ubah_guru_tanpa_sandi_mempertahankan_sandi_lama(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'guru', 'email' => 'guru@sekolah.test']);
        $guru = Guru::create(['user_id' => $user->id, 'nip' => '198504122001011001']);

        $this->actingAs($admin)->put(route('master.guru.update', $guru), [
            'name' => 'Guru Baru, S.Pd',
            'email' => 'guru@sekolah.test',
            'password' => null,
            'nip' => '198504122001011001',
        ])->assertRedirect(route('master.guru'));

        $this->assertCredentials(['email' => 'guru@sekolah.test', 'password' => 'password']);
        $this->assertSame('Guru Baru, S.Pd', $user->fresh()->name);
    }

    public function test_hapus_guru_melakukan_soft_delete_ke_guru_dan_akun(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create(['user_id' => $user->id, 'nip' => '198504122001011001']);

        $this->actingAs($admin)
            ->delete(route('master.guru.destroy', $guru))
            ->assertSessionHas('sukses');

        $this->assertSoftDeleted('guru', ['id' => $guru->id]);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
