<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PetugasController extends Controller
{
    /**
     * Tampilkan daftar akun Petugas Check-in (FR-16 / UC-16)
     */
    public function index(Request $request): View
    {
        $search = $request->query('q');

        $petugasList = User::where('role', 'petugas')
            ->withCount(['events', 'recordedAttendances'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.petugas.index', compact('petugasList', 'search'));
    }

    /**
     * Simpan akun Petugas baru (UC-16 Basic Flow)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash', 'min:4', 'max:50', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'username.unique' => 'Username tidak tersedia karena sudah digunakan oleh akun lain.',
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'petugas',
            'is_active' => true,
        ]);

        return redirect()->route('admin.petugas.index')
            ->with('success', "Akun Petugas '{$validated['name']}' (@{$validated['username']}) berhasil dibuat.");
    }

    /**
     * Perbarui data akun Petugas (UC-16 Alternative Flow)
     */
    public function update(Request $request, User $petugas): RedirectResponse
    {
        abort_unless($petugas->isPetugas(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:4',
                'max:50',
                Rule::unique('users', 'username')->ignore($petugas->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($petugas->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['required', 'boolean'],
        ], [
            'username.unique' => 'Username tidak tersedia karena sudah digunakan oleh akun lain.',
        ]);

        $data = [
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'] ?? null,
            'is_active' => $validated['is_active'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $petugas->update($data);

        return redirect()->route('admin.petugas.index')
            ->with('success', "Data akun Petugas '{$petugas->name}' berhasil diperbarui.");
    }

    /**
     * Aktifkan / Nonaktifkan akun Petugas (UC-16 Alternative Flow: deactivatePetugas)
     */
    public function toggleStatus(User $petugas): RedirectResponse
    {
        abort_unless($petugas->isPetugas(), 404);

        $petugas->update([
            'is_active' => !$petugas->is_active,
        ]);

        $statusLabel = $petugas->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return redirect()->route('admin.petugas.index')
            ->with('success', "Akun Petugas '{$petugas->name}' berhasil {$statusLabel}.");
    }
}
