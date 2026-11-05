<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRole
{
    /**
     * Pastikan akun pengguna masih berstatus aktif dan memiliki role yang diizinkan.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Jika akun dinonaktifkan oleh Admin saat sesi berjalan, paksa logout
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'identity' => 'Akun Anda telah dinonaktifkan oleh Administrator.',
            ]);
        }

        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
