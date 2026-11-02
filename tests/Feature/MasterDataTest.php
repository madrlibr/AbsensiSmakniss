<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function buatTahunAjaran(string $nama, bool $aktif = false): TahunAjaran
    {
        return TahunAjaran::create([
            'nama' => $nama,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'is_active' => $aktif,
        ]);
    }

    private function buatJurusan(): Jurusan
    {
        return Jurusan::create(['nama' => 'Teknik Komputer Jaringan', 'singkatan' => 'TKJ']);
    }

    private function buatKelas(TahunAjaran $ta, Jurusan $j, string $nama = 'X TKJ 1'): Kelas
    {
        return Kelas::create([
            'tahun_ajaran_id' => $ta->id,
            'jurusan_id' => $j->id,
            'nama_kelas' => $nama,
        ]);
    }

    public function test_scope_tahun_ajaran_aktif_dan_helper(): void
    {
        $this->buatTahunAjaran('2025/2026');
        $aktif = $this->buatTahunAjaran('2026/2027', true);

        $this->assertTrue($aktif->is(TahunAjaran::aktif()));
        $this->assertSame(1, TahunAjaran::query()->aktif()->count());
    }

    public function test_role_user_di_cast_ke_enum(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isWaliKelas());
        $this->assertSame('Administrator', $user->role->label());
    }

    public function test_user_nonaktif_tetap_punya_is_active_boolean(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_siswa_otomatis_mendapat_qr_code(): void
    {
        $siswa = Siswa::create([
            'nisn' => '0081234567',
            'nis' => '2026.001',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'laki-laki',
        ]);

        $this->assertSame("SISWA-{$siswa->id}-0081234567", $siswa->fresh()->qr_code);
    }

    public function test_lookup_identitas_via_qr_nisn_atau_nis(): void
    {
        $siswa = Siswa::create([
            'nisn' => '0081234567',
            'nis' => '2026.001',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'laki-laki',
        ]);

        $this->assertTrue(Siswa::cariIdentitas($siswa->qr_code)->is($siswa));
        $this->assertTrue(Siswa::cariIdentitas('0081234567')->is($siswa));
        $this->assertTrue(Siswa::cariIdentitas('2026.001')->is($siswa));
        $this->assertNull(Siswa::cariIdentitas('TIDAK-ADA'));
    }

    public function test_keanggotaan_kelas_lewat_pivot_per_tahun_ajaran(): void
    {
        $ta1 = $this->buatTahunAjaran('2026/2027', true);
        $ta2 = $this->buatTahunAjaran('2027/2028');
        $jurusan = $this->buatJurusan();
        $kelas1 = $this->buatKelas($ta1, $jurusan, 'X TKJ 1');
        $kelas2 = $this->buatKelas($ta2, $jurusan, 'XI TKJ 1');

        $siswa = Siswa::create([
            'nisn' => '0081234567',
            'nis' => '2026.001',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'laki-laki',
        ]);

        SiswaKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas1->id,
            'tahun_ajaran_id' => $ta1->id,
        ]);

        // Tahun ajaran aktif = 2026/2027 → kelas aktif X TKJ 1
        $this->assertTrue($siswa->kelas_aktif->is($kelas1));

        // Naik kelas: pindah ke TA baru tanpa mengubah riwayat TA lama
        SiswaKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas2->id,
            'tahun_ajaran_id' => $ta2->id,
        ]);

        $this->assertSame(2, $siswa->riwayatKelas()->count());
        $this->assertTrue($siswa->kelas_aktif->is($kelas1), 'Kelas aktif harus tetap mengikuti TA aktif');
        $this->assertTrue($siswa->kelasDiTa($ta2->id)->is($kelas2));
    }

    public function test_guru_tertaut_users_dan_mapel(): void
    {
        $user = User::factory()->create(['name' => 'Budi Suryanto, S.Pd', 'role' => 'guru']);
        $mapel = Mapel::create(['kode_mapel' => 'MTK-101', 'nama_mapel' => 'Matematika']);
        $guru = Guru::create([
            'user_id' => $user->id,
            'nip' => '198504122001011001',
            'mapel_utama_id' => $mapel->id,
        ]);

        $this->assertTrue($guru->user->is($user));
        $this->assertSame('Budi Suryanto, S.Pd', $guru->nama);
        $this->assertTrue($guru->mapelUtama->is($mapel));
    }

    public function test_orang_tua_tertaut_user_dan_siswa(): void
    {
        $user = User::factory()->create(['role' => 'orang_tua']);
        $ibu = OrangTua::create(['user_id' => $user->id, 'nama' => 'Ibu Sari', 'telepon' => '081234567890']);

        $siswa = Siswa::create([
            'nisn' => '0081234567',
            'nis' => '2026.001',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'laki-laki',
        ]);
        $siswa->orangTua()->attach($ibu->id, ['hubungan' => 'ibu', 'is_primary' => true]);

        $this->assertTrue($ibu->user->is($user));
        $this->assertTrue($ibu->siswa->first()->is($siswa));
        $this->assertTrue($siswa->orangTua->first()->pivot->is_primary);
        $this->assertTrue($user->orangTua->is($ibu));
    }
}
