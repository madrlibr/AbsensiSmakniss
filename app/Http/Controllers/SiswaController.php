<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiswaController extends Controller
{
    public function index(Request $request): View
    {
        $ta = TahunAjaran::aktif();

        $query = Siswa::query(); // kelasAktif diakses via accessor per baris (bukan relasi eager-load)

        // Status default: aktif
        $status = $request->string('status', 'aktif')->toString();
        $query->when($status === 'aktif', fn ($q) => $q->aktif())
            ->when($status === 'nonaktif', fn ($q) => $q->where('active', false));

        if ($request->filled('q')) {
            $kata = $request->string('q')->toString();
            $query->where(fn ($q) => $q
                ->where('nisn', 'like', "%{$kata}%")
                ->orWhere('nis', 'like', "%{$kata}%")
                ->orWhere('nama', 'like', "%{$kata}%"));
        }

        if ($request->filled('kelas_id')) {
            $kelasId = $request->integer('kelas_id');
            $query->whereHas('riwayatKelas', fn ($q) => $q
                ->where('kelas_id', $kelasId)
                ->where('is_active', true));
        }

        return view('master.siswa.index', [
            'daftar' => $query->orderBy('nama')->paginate(10)->withQueryString(),
            'status' => $status,
            'ta' => $ta,
            'kelasPilihan' => $ta
                ? Kelas::where('tahun_ajaran_id', $ta->id)->with('jurusan')->orderBy('nama_kelas')->get()
                : collect(),
            'kpi' => $this->kpi($ta),
        ]);
    }

    public function create(): View
    {
        return view('master.siswa.form', [
            'siswa' => null,
            'kelasPilihan' => $this->opsiKelasTaAktif(),
        ]);
    }

    public function edit(Siswa $siswa): View
    {
        return view('master.siswa.form', [
            'siswa' => $siswa,
            'kelasPilihan' => $this->opsiKelasTaAktif(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $siswa = Siswa::create([
            'nisn' => $data['nisn'],
            'nis' => $data['nis'],
            'nama' => $data['nama'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'active' => $data['active'] ?? true,
        ]);

        $this->sinkronKelas($siswa, $data['kelas_id'] ?? null);

        return redirect()
            ->route('master.siswa')
            ->with('sukses', "Siswa {$siswa->nama} terdaftar — QR kartu pelajar sudah digenerate.");
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $siswa->update($this->validasi($request, $siswa));

        $this->sinkronKelas($siswa, $request->input('kelas_id'));

        return redirect()
            ->route('master.siswa')
            ->with('sukses', "Data {$siswa->nama} diperbarui.");
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        // Keanggotaan kelas dinonaktifkan; riwayat absensi & kelas tetap utuh
        $siswa->riwayatKelas()->where('is_active', true)->update(['is_active' => false]);
        $siswa->delete();

        return redirect()
            ->route('master.siswa')
            ->with('sukses', "Siswa {$siswa->nama} dihapus (soft delete).");
    }

    /* ── Bantuan ───────────────────────────────────────────── */

    /** Set/ubah keanggotaan kelas pada tahun ajaran aktif (tanpa menyentuh TA lama). */
    private function sinkronKelas(Siswa $siswa, mixed $kelasId): void
    {
        $ta = TahunAjaran::aktif();

        if (! $ta || ! $kelasId) {
            return; // Tanpa pilihan kelas: keanggotaan yang ada dibiarkan apa adanya
        }

        SiswaKelas::updateOrCreate(
            ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $ta->id],
            ['kelas_id' => (int) $kelasId, 'is_active' => true],
        );
    }

    private function validasi(Request $request, ?Siswa $siswa = null): array
    {
        return $request->validate([
            'nisn' => ['required', 'string', 'max:20', Rule::unique('siswa', 'nisn')->ignore($siswa?->id)],
            'nis' => ['required', 'string', 'max:20', Rule::unique('siswa', 'nis')->ignore($siswa?->id)],
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['laki-laki', 'perempuan'])],
            'active' => ['nullable', 'boolean'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
        ], [
            'nisn.unique' => 'NISN sudah terdaftar — periksa data siswa lain.',
            'nis.unique' => 'NIS sudah terdaftar.',
        ]);
    }

    private function opsiKelasTaAktif()
    {
        $ta = TahunAjaran::aktif();

        return $ta
            ? Kelas::where('tahun_ajaran_id', $ta->id)
                ->with('jurusan')
                ->orderBy('nama_kelas')
                ->get()
            : collect();
    }

    private function kpi(?TahunAjaran $ta): array
    {
        $dasar = fn () => Siswa::query()->aktif();

        return [
            'total' => $dasar()->count(),
            'laki' => $dasar()->where('jenis_kelamin', 'laki-laki')->count(),
            'perempuan' => $dasar()->where('jenis_kelamin', 'perempuan')->count(),
            'tanpa_kelas' => $ta
                ? $dasar()->whereDoesntHave('riwayatKelas', fn ($q) => $q
                    ->where('tahun_ajaran_id', $ta->id)
                    ->where('is_active', true))->count()
                : 0,
        ];
    }
}
