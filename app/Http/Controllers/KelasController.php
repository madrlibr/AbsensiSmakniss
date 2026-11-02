<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $tahunAjaran = $request->filled('ta')
            ? TahunAjaran::findOrFail($request->integer('ta'))
            : TahunAjaran::aktif();

        return view('master.kelas.index', [
            'daftar' => Kelas::query()
                ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
                ->with(['jurusan', 'waliKelas', 'tahunAjaran'])
                ->withCount(['siswaKelas as siswa_count' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('nama_kelas')
                ->paginate(10)
                ->withQueryString(),
            'tahunAjaran' => $tahunAjaran,
            'daftarTahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.kelas.form', [
            'kelas' => null,
            'daftarTahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'daftarJurusan' => \App\Models\Jurusan::orderBy('nama')->pluck('nama', 'id'),
            'daftarWali' => $this->opsiWaliKelas(),
        ]);
    }

    public function edit(Kelas $kelas): View
    {
        return view('master.kelas.form', [
            'kelas' => $kelas,
            'daftarTahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'daftarJurusan' => \App\Models\Jurusan::orderBy('nama')->pluck('nama', 'id'),
            'daftarWali' => $this->opsiWaliKelas(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $kelas = Kelas::create($this->validasi($request));

        return redirect()
            ->route('master.kelas.anggota', $kelas)
            ->with('sukses', "Kelas {$kelas->nama_kelas} dibuat. Sekarang tambahkan anggotanya.");
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($this->validasi($request, $kelas));

        return redirect()
            ->route('master.kelas')
            ->with('sukses', "Kelas {$kelas->nama_kelas} diperbarui.");
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        if ($kelas->siswaKelas()->where('is_active', true)->exists()) {
            return back()->with('gagal', 'Kelas masih memiliki anggota aktif. Keluarkan siswanya terlebih dahulu.');
        }

        if ($kelas->jadwalPelajaran()->exists()) {
            return back()->with('gagal', 'Kelas masih memiliki jadwal pelajaran dan tidak dapat dihapus.');
        }

        $kelas->delete();

        return redirect()
            ->route('master.kelas')
            ->with('sukses', "Kelas {$kelas->nama_kelas} dihapus.");
    }

    /* ── Anggota kelas (pivot siswa_kelas per tahun ajaran) ─── */

    public function anggota(Kelas $kelas): View
    {
        $kelas->load(['jurusan', 'waliKelas', 'tahunAjaran']);

        $anggota = SiswaKelas::query()
            ->where('kelas_id', $kelas->id)
            ->where('is_active', true)
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama')
            ->values();

        // Siswa aktif yang belum terdaftar di kelas mana pun pada tahun ajaran ini
        $belumPunyaKelas = Siswa::aktif()
            ->whereDoesntHave('riwayatKelas', fn ($q) => $q
                ->where('tahun_ajaran_id', $kelas->tahun_ajaran_id)
                ->where('is_active', true))
            ->orderBy('nama')
            ->get();

        return view('master.kelas.anggota', [
            'kelas' => $kelas,
            'anggota' => $anggota,
            'belumPunyaKelas' => $belumPunyaKelas,
            'kelasSejenis' => Kelas::query()
                ->where('tahun_ajaran_id', $kelas->tahun_ajaran_id)
                ->where('id', '!=', $kelas->id)
                ->orderBy('nama_kelas')
                ->pluck('nama_kelas', 'id'),
        ]);
    }

    public function tambahAnggota(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $request->validate([
            'siswa_id' => ['required', 'exists:siswa,id'],
        ], [
            'siswa_id.required' => 'Pilih siswa yang akan ditambahkan.',
        ]);

        $sudahAda = SiswaKelas::query()
            ->where('siswa_id', $data['siswa_id'])
            ->where('tahun_ajaran_id', $kelas->tahun_ajaran_id)
            ->where('is_active', true)
            ->exists();

        if ($sudahAda) {
            return back()->with('gagal', 'Siswa sudah terdaftar di sebuah kelas tahun ajaran ini. Gunakan aksi "Pindah" untuk memindahkannya.');
        }

        // Reactivate baris lama bila pernah dikeluarkan dari TA yang sama (unique siswa+TA)
        SiswaKelas::updateOrCreate(
            ['siswa_id' => $data['siswa_id'], 'tahun_ajaran_id' => $kelas->tahun_ajaran_id],
            ['kelas_id' => $kelas->id, 'is_active' => true],
        );

        return back()->with('sukses', 'Siswa ditambahkan sebagai anggota kelas.');
    }

    public function pindahAnggota(Request $request, SiswaKelas $siswaKelas): RedirectResponse
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
        ], [
            'kelas_id.required' => 'Pilih kelas tujuan.',
        ]);

        $kelas = Kelas::findOrFail($data['kelas_id']);

        if ($kelas->tahun_ajaran_id !== $siswaKelas->tahun_ajaran_id) {
            return back()->with('gagal', 'Kelas tujuan harus berada di tahun ajaran yang sama.');
        }

        if ($kelas->id === $siswaKelas->kelas_id) {
            return back()->with('gagal', 'Siswa sudah berada di kelas tersebut.');
        }

        // Riwayat tahun ajaran lama tidak tersentuh — hanya baris TA berjalan yang berubah
        $siswaKelas->update(['kelas_id' => $kelas->id, 'is_active' => true]);

        return back()->with('sukses', "Siswa dipindahkan ke {$kelas->nama_kelas}.");
    }

    public function keluarkanAnggota(SiswaKelas $siswaKelas): RedirectResponse
    {
        // Baris tetap ada (riwayat), hanya dinonaktifkan
        $siswaKelas->update(['is_active' => false]);

        return back()->with('sukses', 'Siswa dikeluarkan dari kelas.');
    }

    /* ── Validasi & opsi ───────────────────────────────────── */

    private function validasi(Request $request, ?Kelas $kelas = null): array
    {
        return $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'jurusan_id' => ['required', 'exists:jurusan,id'],
            'nama_kelas' => [
                'required', 'string', 'max:30',
                Rule::unique('kelas', 'nama_kelas')
                    ->where('tahun_ajaran_id', $request->integer('tahun_ajaran_id'))
                    ->ignore($kelas?->id),
            ],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
        ], [
            'nama_kelas.unique' => 'Nama kelas sudah dipakai di tahun ajaran tersebut.',
        ]);
    }

    /** Opsi wali kelas: user dengan role guru / wali kelas yang aktif. */
    private function opsiWaliKelas()
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [Role::Guru->value, Role::WaliKelas->value])
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
