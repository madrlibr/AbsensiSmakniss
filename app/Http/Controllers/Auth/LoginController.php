<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /** Tampil halaman login (tamunya sudah login → lempar ke halamannya). */
    public function create(): View
    {
        return view('auth.login');
    }

    /** Proses login manual (session), dengan throttle & cek is_active. */
    public function store(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $kunci = Str::lower($kredensial['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.',
            ]);
        }

        if (! Auth::attempt($kredensial, $request->boolean('remember'))) {
            RateLimiter::hit($kunci, 60);

            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }

        $user = Auth::user();

        // is_active=false tidak boleh masuk (sesuai desain §4)
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => 'Akun Anda dinonaktifkan. Hubungi admin sekolah.']);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        return redirect()->intended($this->tujuanRole($user->role));
    }

    /** Logout: hapus sesi & token. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Tujuan setelah login sesuai role. */
    private function tujuanRole(Role $role): string
    {
        return match ($role) {
            Role::OrangTua => route('portal.index'),
            default => route('dashboard'),
        };
    }
}
