<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TahunAjaranController extends Controller
{
    public function index(): View
    {
        return view('master.tahun-ajaran.index', [
            'daftar' => TahunAjaran::orderByDesc('tanggal_mulai')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.tahun-ajaran.form', ['tahunAjaran' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:20', Rule::unique('tahun_ajaran', 'nama')],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        TahunAjaran::create($data);

        return redirect()
            ->route('master.tahun-ajaran')
            ->with('sukses', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return view('master.tahun-ajaran.form', ['tahunAjaran' => $tahunAjaran]);
    }

    public function update(Request $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:20', Rule::unique('tahun_ajaran', 'nama')->ignore($tahunAjaran->id)],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $tahunAjaran->update($data);

        return redirect()
            ->route('master.tahun-ajaran')
            ->with('sukses', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if ($tahunAjaran->is_active) {
            return back()->with('gagal', 'Tahun ajaran aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.');
        }

        $tahunAjaran->delete();

        return redirect()
            ->route('master.tahun-ajaran')
            ->with('sukses', 'Tahun ajaran dihapus.');
    }

    /** Hanya satu tahun ajaran yang aktif — dialihkan dalam satu transaksi. */
    public function jadikanAktif(TahunAjaran $tahunAjaran): RedirectResponse
    {
        DB::transaction(function () use ($tahunAjaran): void {
            TahunAjaran::query()->update(['is_active' => false]);
            $tahunAjaran->update(['is_active' => true]);
        });

        return back()->with('sukses', "Tahun ajaran {$tahunAjaran->nama} kini menjadi aktif.");
    }
}
