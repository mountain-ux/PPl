<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman Login Admin / Petugas Check-in (UC-01)
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi menggunakan username atau email + password (UC-01, NFR-01, NFR-02)
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'identity.required' => 'Username atau Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['identity']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'identity' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Cari user berdasarkan email atau username
        $fieldType = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($fieldType, $credentials['identity'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'identity' => 'Username/Email atau password yang Anda masukkan salah.',
            ]);
        }

        // Periksa apakah akun aktif (Activity Diagram Login SDD Hal. 41-42)
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'identity' => 'Akun Anda berstatus tidak aktif. Silakan hubungi Administrator.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Arahkan sesuai peran (Postcondition UC-01)
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', "Selamat datang kembali, {$user->name}!");
        }

        return redirect()->intended(route('events.index'))
            ->with('success', "Berhasil masuk sebagai Petugas Check-in ({$user->name}).");
    }

    /**
     * Proses Logout dan bersihkan sesi
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dari sistem.');
    }
}
