<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\TahunAjaran;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CRUD hari libur rentang tanggal (Task 9).
 * status_masuk: tidak_masuk = libur mutlak · masuk = kegiatan (hari dipaksa aktif).
 */
class HariLiburController extends Controller
{
    public function index(): View
    {
        $ta = TahunAjaran::aktif();

        return view('pengaturan.libur.index', [
            'daftar' => HariLibur::query()
                ->when($ta, fn ($q) => $q->where('tahun_ajaran_id', $ta->id))
                ->orderBy('tanggal_mulai')
                ->get(),
            'ta' => $ta,
        ]);
    }

    public function create(): View
    {
        return view('pengaturan.libur.form', [
            'libur' => null,
            'ta' => TahunAjaran::aktif(),
            'daftarTahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function edit(HariLibur $hariLibur): View
    {
        return view('pengaturan.libur.form', [
            'libur' => $hariLibur,
            'ta' => $hariLibur->tahunAjaran,
            'daftarTahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        HariLibur::create($this->validasi($request));

        return redirect()
            ->route('pengaturan.libur')
            ->with('sukses', 'Hari libur / agenda tersimpan.');
    }

    public function update(Request $request, HariLibur $hariLibur): RedirectResponse
    {
        $hariLibur->update($this->validasi($request));

        return redirect()
            ->route('pengaturan.libur')
            ->with('sukses', 'Hari libur / agenda diperbarui.');
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $hariLibur->delete();

        return redirect()
            ->route('pengaturan.libur')
            ->with('sukses', 'Hari libur dihapus.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['required', 'string', 'max:120'],
            'status_masuk' => ['required', 'in:tidak_masuk,masuk'],
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);
    }
}
