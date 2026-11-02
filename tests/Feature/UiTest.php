<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 4: fondasi UI/UX — halaman login, galeri komponen, menu per role.
 */
class UiTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_tampil_dengan_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('SMK Attendance OS');
    }

    public function test_galeri_komponen_tampil_dan_memuat_semua_varian(): void
    {
        $this->get('/ui')
            ->assertOk()
            ->assertSee('Galeri Komponen UI')
            ->assertSee('Hadir Tepat Waktu')
            ->assertSee('Kabur');
    }

    public function test_menu_admin_melihat_master_data_dan_pengaturan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $menu = Menu::untuk($admin);

        $this->assertArrayHasKey('Master Data Sekolah', $menu);
        $this->assertArrayHasKey('Manajemen Sistem', $menu);
        $this->assertArrayNotHasKey('Portal Orang Tua', $menu, 'Grup ortu tidak boleh tampil untuk admin');
    }

    public function test_menu_osis_hanya_operasional_dan_koreksi(): void
    {
        $osis = User::factory()->create(['role' => 'osis']);
        $menu = Menu::untuk($osis);

        $this->assertArrayHasKey('Operasional Harian', $menu);
        $this->assertArrayNotHasKey('Master Data Sekolah', $menu);

        $rute = collect($menu['Operasional Harian'])->pluck('route');
        $this->assertTrue($rute->contains('scan.gerbang'));
        $this->assertFalse($rute->contains('absensi.mapel'));

        // Di grup Laporan, osis hanya boleh melihat Koreksi Data
        $this->assertArrayHasKey('Laporan & Administrasi', $menu);
        $this->assertSame(['koreksi.index'], collect($menu['Laporan & Administrasi'])->pluck('route')->all());
    }

    public function test_menu_orang_tua_hanya_portal(): void
    {
        $ortu = User::factory()->create(['role' => 'orang_tua']);
        $menu = Menu::untuk($ortu);

        $this->assertArrayHasKey('Portal Orang Tua', $menu);
        $this->assertArrayNotHasKey('Master Data Sekolah', $menu);
        $this->assertArrayNotHasKey('Operasional Harian', $menu);
    }

    public function test_menu_tanpa_user_kosong(): void
    {
        $this->assertSame([], Menu::untuk(null));
    }
}
