@extends('layouts.app')

@section('title', 'Kelola Akun Petugas — TAMUIN')

@section('content')
<div class="mb-5">
    <h1 class="text-xl font-bold text-slate-900">Kelola Akun Petugas Check-in</h1>
    <p class="text-xs text-slate-500 mt-0.5">Tambahkan, perbarui, atau atur status aktif akun petugas operasional kiosk.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="bg-white rounded-2xl border border-slate-200 p-5 h-fit">
        <h2 class="text-sm font-bold text-slate-900 mb-4">Tambah Petugas Baru</h2>
        <form action="{{ route('admin.petugas.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="Masukkan nama lengkap petugas"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Username Login *</label>
                <input type="text" name="username" value="{{ old('username') }}" required
                       placeholder="Contoh: petugas02"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email (Opsional)</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="petugas@email.com"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Awal *</label>
                <input type="password" name="password" required minlength="6"
                       placeholder="Minimal 6 karakter"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 outline-none">
            </div>

            <button type="submit" class="w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2.5 text-xs sm:text-sm transition">
                Simpan Akun Petugas
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h2 class="text-sm font-bold text-slate-900">Daftar Akun Petugas</h2>
            <form method="GET" action="{{ route('admin.petugas.index') }}" class="flex w-full sm:w-auto gap-2">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau username..."
                       class="flex-1 w-full sm:w-60 rounded-xl border border-slate-300 px-3 py-1.5 text-xs focus:border-blue-600 outline-none">
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shrink-0">Cari</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] text-slate-500 uppercase">
                        <th class="py-2.5 px-3">Nama Petugas</th>
                        <th class="py-2.5 px-3">Username</th>
                        <th class="py-2.5 px-3">Acara</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($petugasList as $ptg)
                        <tr>
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-900">{{ $ptg->name }}</div>
                                <div class="text-xs text-slate-500">{{ $ptg->email ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-3 font-mono text-xs text-slate-700">{{ $ptg->username }}</td>
                            <td class="py-3 px-3 text-slate-600">{{ $ptg->events_count }}</td>
                            <td class="py-3 px-3">
                                @if($ptg->is_active)
                                    <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                <form action="{{ route('admin.petugas.toggle-status', $ptg) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Ubah status aktif akun {{ addslashes($ptg->name) }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $ptg->is_active ? 'border border-rose-200 text-rose-600 hover:bg-rose-50' : 'border border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                        {{ $ptg->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-xs">Belum ada akun petugas terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $petugasList->links() }}
        </div>
    </div>
</div>
@endsection
