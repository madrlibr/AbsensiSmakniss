<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MapelController extends Controller
{
    public function index(): View
    {
        return view('master.mapel.index', [
            'daftar' => Mapel::orderBy('kode_mapel')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.mapel.form', ['mapel' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Mapel::create($this->validasi($request));

        return redirect()
            ->route('master.mapel')
            ->with('sukses', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(Mapel $mapel): View
    {
        return view('master.mapel.form', ['mapel' => $mapel]);
    }

    public function update(Request $request, Mapel $mapel): RedirectResponse
    {
        $mapel->update($this->validasi($request, $mapel));

        return redirect()
            ->route('master.mapel')
            ->with('sukses', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Mapel $mapel): RedirectResponse
    {
        if ($mapel->jadwalPelajaran()->exists()) {
            return back()->with('gagal', 'Mata pelajaran masih dipakai di jadwal dan tidak dapat dihapus.');
        }

        $mapel->delete();

        return redirect()
            ->route('master.mapel')
            ->with('sukses', 'Mata pelajaran dihapus.');
    }

    private function validasi(Request $request, ?Mapel $mapel = null): array
    {
        return $request->validate([
            'kode_mapel' => [
                'required', 'string', 'max:15',
                Rule::unique('mapel', 'kode_mapel')->ignore($mapel?->id),
            ],
            'nama_mapel' => ['required', 'string', 'max:100'],
            'jumlah_jam_per_minggu' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
    }
}
