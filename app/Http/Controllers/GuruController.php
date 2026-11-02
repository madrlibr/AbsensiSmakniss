<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    public function index(): View
    {
        return view('master.guru.index', [
            'daftar' => Guru::with(['user', 'mapelUtama'])->orderBy('nip')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.guru.form', [
            'guru' => null,
            'mapel' => Mapel::orderBy('nama_mapel')->get(),
        ]);
    }

    public function edit(Guru $guru): View
    {
        return view('master.guru.form', [
            'guru' => $guru,
            'mapel' => Mapel::orderBy('nama_mapel')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'nip' => ['required', 'string', 'max:30', Rule::unique('guru', 'nip')],
            'telepon' => ['nullable', 'string', 'max:20'],
            'mapel_utama_id' => ['nullable', 'exists:mapel,id'],
        ]);

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => Role::Guru,
                'is_active' => true,
            ]);

            Guru::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'telepon' => $data['telepon'] ?? null,
                'mapel_utama_id' => $data['mapel_utama_id'] ?? null,
            ]);
        });

        return redirect()
            ->route('master.guru')
            ->with('sukses', 'Guru berhasil ditambahkan.');
    }

    public function update(Request $request, Guru $guru): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($guru->user_id)],
            'password' => ['nullable', 'string', 'min:8'],
            'nip' => ['required', 'string', 'max:30', Rule::unique('guru', 'nip')->ignore($guru->id)],
            'telepon' => ['nullable', 'string', 'max:20'],
            'mapel_utama_id' => ['nullable', 'exists:mapel,id'],
        ]);

        DB::transaction(function () use ($guru, $data): void {
            $atributUser = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if ($data['password'] !== null && $data['password'] !== '') {
                $atributUser['password'] = $data['password'];
            }

            $guru->user->update($atributUser);

            $guru->update([
                'nip' => $data['nip'],
                'telepon' => $data['telepon'] ?? null,
                'mapel_utama_id' => $data['mapel_utama_id'] ?? null,
            ]);
        });

        return redirect()
            ->route('master.guru')
            ->with('sukses', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Guru $guru): RedirectResponse
    {
        DB::transaction(function () use ($guru): void {
            $guru->user?->delete(); // soft delete akun login
            $guru->delete();
        });

        return redirect()
            ->route('master.guru')
            ->with('sukses', 'Guru dihapus.');
    }
}
