<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proteksi rute berbasis role: route('…')->middleware('role:admin,bk').
 * Dipakai bersama middleware bawaan 'auth'.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->value;

        abort_unless(
            $role !== null && in_array($role, $roles, true),
            403,
            'Maaf, Anda tidak memiliki akses ke halaman ini.'
        );

        return $next($request);
    }
}
