@extends('layouts.app')

@section('title', 'Login Sistem — TAMUIN')

@section('content')
@php
    $runningEvent = \App\Models\Event::orderByDesc('event_date')->first();
@endphp
<div class="min-h-[75vh] flex items-center justify-center py-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xs border border-slate-200 p-6 sm:p-8">
        <div class="text-center mb-6">
            <div class="w-11 h-11 rounded-xl bg-blue-700 text-white font-bold text-xl flex items-center justify-center mx-auto mb-3">
                T
            </div>
            <h1 class="text-lg font-bold text-slate-900">Masuk ke Sistem TAMUIN</h1>
            <p class="text-xs text-slate-500 mt-1">
                Autentikasi untuk Administrator dan Petugas Check-in
            </p>
        </div>

        @if($runningEvent)
        <div class="mb-5 rounded-xl bg-slate-50 border border-slate-200 p-3.5">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-blue-700">Acara Aktif</div>
            <div class="text-xs font-bold text-slate-900 truncate mt-0.5">{{ $runningEvent->name }}</div>
            <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ $runningEvent->location }}</div>
        </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="identity" class="block text-xs font-semibold text-slate-700 mb-1">Username atau Email</label>
                <input type="text" id="identity" name="identity" value="{{ old('identity') }}" required autofocus
                       placeholder="Masukkan username atau alamat email"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                           placeholder="Masukkan kata sandi"
                           class="w-full rounded-xl border border-slate-300 bg-white pl-3.5 pr-14 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">
                    <button type="button" onclick="const p=document.getElementById('password'); p.type=p.type==='password'?'text':'password'; this.textContent=p.type==='password'?'Lihat':'Tutup';"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-slate-500 hover:text-slate-700">
                        Lihat
                    </button>
                </div>
            </div>

            <div class="pt-0.5">
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-600">
                    <input type="checkbox" name="remember" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Ingat sesi login pada perangkat ini</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2.5 text-sm transition">
                Masuk
            </button>
        </form>
    </div>
</div>
@endsection
