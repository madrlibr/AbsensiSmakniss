<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 5: auth login + guard role + proteksi rute.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(string $role, array $atribut = []): User
    {
        return User::factory()->create(array_merge(['role' => $role], $atribut));
    }

    /* ── Login ─────────────────────────────────────────────────────────── */

    public function test_login_admin_mengarah_ke_dashboard(): void
    {
        $user = $this->buatUser('admin');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_orang_tua_mengarah_ke_portal(): void
    {
        $user = $this->buatUser('orang_tua');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('portal.index'));
    }

    public function test_login_password_salah_ditolak(): void
    {
        $user = $this->buatUser('admin');

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_terdaftar_tapi_user_nonaktif_ditolak(): void
    {
        $user = $this->buatUser('admin', ['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_mengakhiri_sesi_dan_kembali_ke_login(): void
    {
        $user = $this->buatUser('guru');
        $this->actingAs($user);

        $this->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /* ── Proteksi rute ─────────────────────────────────────────────────── */

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/master/siswa')->assertRedirect(route('login'));
    }

    public function test_osis_bisa_scan_tapi_ditolak_master_siswa(): void
    {
        $osis = $this->buatUser('osis');

        $this->actingAs($osis)->get('/scan/gerbang')->assertOk();
        $this->actingAs($osis)->get('/master/siswa')->assertForbidden();
    }

    public function test_guru_bisa_absen_mapel_tapi_ditolak_master(): void
    {
        $guru = $this->buatUser('guru');

        $this->actingAs($guru)->get('/absensi/mapel')->assertOk();
        $this->actingAs($guru)->get('/master/guru')->assertForbidden();
        $this->actingAs($guru)->get('/rekap')->assertForbidden();
    }

    public function test_piket_sama_dengan_osis_scan_dan_koreksi(): void
    {
        $piket = $this->buatUser('piket');

        $this->actingAs($piket)->get('/scan/gerbang')->assertOk();
        $this->actingAs($piket)->get('/koreksi')->assertOk();
        $this->actingAs($piket)->get('/absensi/mapel')->assertForbidden();
        $this->actingAs($piket)->get('/rekap')->assertForbidden();
    }

    public function test_admin_bisa_semua_modul_master(): void
    {
        $admin = $this->buatUser('admin');

        foreach (['master.mapel', 'master.kelas', 'master.siswa', 'master.guru', 'jadwal.index', 'pengaturan.hari'] as $rute) {
            $this->actingAs($admin)->get(route($rute))->assertOk();
        }
    }

    public function test_orang_tua_ditolak_dashboard_dan_scan(): void
    {
        $ortu = $this->buatUser('orang_tua');

        $this->actingAs($ortu)->get('/dashboard')->assertForbidden();
        $this->actingAs($ortu)->get('/scan/gerbang')->assertForbidden();
        $this->actingAs($ortu)->get('/portal')->assertOk();
    }

    public function test_wali_kelas_bisa_pengajuan_dan_rekap(): void
    {
        $wali = $this->buatUser('wali_kelas');

        $this->actingAs($wali)->get('/pengajuan')->assertOk();
        $this->actingAs($wali)->get('/rekap')->assertOk();
        $this->actingAs($wali)->get('/rekap/laporan-bk')->assertForbidden();
    }

    public function test_bk_bisa_laporan_dan_rekap_tapi_tidak_pengajuan(): void
    {
        $bk = $this->buatUser('bk');

        $this->actingAs($bk)->get('/rekap/laporan-bk')->assertOk();
        $this->actingAs($bk)->get('/rekap')->assertOk();
        $this->actingAs($bk)->get('/pengajuan')->assertForbidden();
    }
}
