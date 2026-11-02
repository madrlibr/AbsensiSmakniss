<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JurusanController extends Controller
{
    public function index(): View
    {
        return view('master.jurusan.index', [
            'daftar' => Jurusan::withCount('kelas')->orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.jurusan.form', ['jurusan' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        Jurusan::create($data);

        return redirect()
            ->route('master.jurusan')
            ->with('sukses', 'Jurusan berhasil ditambahkan.');
    }

    public function edit(Jurusan $jurusan): View
    {
        return view('master.jurusan.form', ['jurusan' => $jurusan]);
    }

    public function update(Request $request, Jurusan $jurusan): RedirectResponse
    {
        $jurusan->update($this->validasi($request, $jurusan));

        return redirect()
            ->route('master.jurusan')
            ->with('sukses', 'Jurusan berhasil diperbarui.');
    }

    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        if ($jurusan->kelas()->exists()) {
            return back()->with('gagal', 'Jurusan masih memiliki kelas dan tidak dapat dihapus.');
        }

        $jurusan->delete();

        return redirect()
            ->route('master.jurusan')
            ->with('sukses', 'Jurusan dihapus.');
    }

    private function validasi(Request $request, ?Jurusan $jurusan = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'singkatan' => [
                'required', 'string', 'max:10',
                Rule::unique('jurusan', 'singkatan')->ignore($jurusan?->id),
            ],
        ]);
    }
}
