<?php

namespace App\Http\Controllers;

use App\Enums\Hari;
use App\Enums\Semester;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TahunAjaran;
use App\Services\Jadwal\ValidasiBentrok;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CRUD jadwal pelajaran (admin) — wali kelas hanya melihat (Task 8).
 * Semua penyimpanan melewati ValidasiBentrok (kelas/guru/ruangan).
 */
class JadwalPelajaranController extends Controller
{
    public function __construct(private ValidasiBentrok $validasiBentrok) {}

    public function index(Request $request): View
    {
        $ta = TahunAjaran::aktif();

        $query = JadwalPelajaran::query()
            ->when($ta, fn ($q) => $q->where('tahun_ajaran_id', $ta->id))
            ->with(['kelas.jurusan', 'mapel', 'guru.user']);

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->integer('kelas_id'));
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->string('semester')->toString());
        }

        // Urut per hari kerja lalu jam ke — tampilan tabel rapi tanpa kelompok khusus
        $urutanHari = collect(Hari::semua())->pluck('value')->flip();
        $daftar = $query->get()
            ->sortBy(fn (JadwalPelajaran $j) => [$urutanHari[$j->hari->value] ?? 9, $j->jam_ke])
            ->values();

        return view('master.jadwal.index', [
            'daftar' => $daftar,
            'ta' => $ta,
            'kelasPilihan' => $ta
                ? Kelas::where('tahun_ajaran_id', $ta->id)->with('jurusan')->orderBy('nama_kelas')->get()
                : collect(),
            'filterKelas' => $request->input('kelas_id'),
            'filterSemester' => $request->input('semester'),
            'semesterPilihan' => collect(Semester::semua())->mapWithKeys(fn (Semester $s) => [$s->value => $s->label()]),
        ]);
    }

    public function create(Request $request): View
    {
        return view('master.jadwal.form', [
            'jadwal' => null,
            'ta' => TahunAjaran::aktif(),
            'kelasPilihan' => $this->kelasTaAktif(),
            'mapelPilihan' => Mapel::orderBy('nama_mapel')->pluck('nama_mapel', 'id'),
            'guruPilihan' => $this->opsiGuru(),
            'hariPilihan' => collect(Hari::semua())->mapWithKeys(fn (Hari $h) => [$h->value => $h->label()]),
            'semesterPilihan' => collect(Semester::semua())->mapWithKeys(fn (Semester $s) => [$s->value => $s->label()]),
        ]);
    }

    public function edit(JadwalPelajaran $jadwal): View
    {
        return view('master.jadwal.form', [
            'jadwal' => $jadwal,
            'ta' => $jadwal->tahunAjaran,
            'kelasPilihan' => $this->kelasTaAktif(),
            'mapelPilihan' => Mapel::orderBy('nama_mapel')->pluck('nama_mapel', 'id'),
            'guruPilihan' => $this->opsiGuru(),
            'hariPilihan' => collect(Hari::semua())->mapWithKeys(fn (Hari $h) => [$h->value => $h->label()]),
            'semesterPilihan' => collect(Semester::semua())->mapWithKeys(fn (Semester $s) => [$s->value => $s->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasiInput($request);

        if ($pesan = $this->validasiBentrok->cek($data)) {
            return back()->withInput()->with('gagal', $pesan);
        }

        JadwalPelajaran::create($data + ['active' => true]);

        return redirect()
            ->route('jadwal.index')
            ->with('sukses', 'Jadwal pelajaran ditambahkan.');
    }

    public function update(Request $request, JadwalPelajaran $jadwal): RedirectResponse
    {
        $data = $this->validasiInput($request);

        // Baris sendiri diabaikan agar jadwal tidak "bentrok" dengan dirinya saat diubah
        if ($pesan = $this->validasiBentrok->cek($data, $jadwal)) {
            return back()->withInput()->with('gagal', $pesan);
        }

        $jadwal->update($data);

        return redirect()
            ->route('jadwal.index')
            ->with('sukses', 'Jadwal pelajaran diperbarui.');
    }

    public function destroy(JadwalPelajaran $jadwal): RedirectResponse
    {
        $jadwal->delete();

        return redirect()
            ->route('jadwal.index')
            ->with('sukses', 'Jadwal pelajaran dihapus.');
    }

    /* ── Bantuan ───────────────────────────────────────────── */

    private function validasiInput(Request $request): array
    {
        return $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mapel_id' => ['required', 'exists:mapel,id'],
            'guru_id' => ['required', 'exists:guru,id'],
            'hari' => ['required', 'in:'.implode(',', collect(Hari::semua())->pluck('value')->all())],
            'jam_ke' => ['required', 'integer', 'min:1', 'max:8'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'ruangan' => ['nullable', 'string', 'max:40'],
            'semester' => ['required', 'in:'.implode(',', collect(Semester::semua())->pluck('value')->all())],
        ], [
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ]);
    }

    private function kelasTaAktif()
    {
        $ta = TahunAjaran::aktif();

        return $ta
            ? Kelas::where('tahun_ajaran_id', $ta->id)->with('jurusan')->orderBy('nama_kelas')->get()
            : collect();
    }

    /** Opsi guru: semua baris guru dengan nama dari relasi user. */
    private function opsiGuru()
    {
        return Guru::query()
            ->with('user')
            ->orderBy('nip')
            ->get()
            ->mapWithKeys(fn (Guru $g) => [$g->id => $g->nama.' · NIP '.$g->nip]);
    }
}
