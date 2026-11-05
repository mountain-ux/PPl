@extends('layouts.app')

@section('title', $event->name . ' — Registrasi Tamu')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
    <!-- Kolom Kiri: Informasi Acara -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
        <div class="flex items-center justify-between gap-2 mb-4">
            <span class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[11px] font-semibold">
                Undangan & Registrasi Acara
            </span>
            <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold {{ $event->is_registration_open ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                {{ $event->is_registration_open ? 'Registrasi Dibuka' : 'Registrasi Ditutup' }}
            </span>
        </div>

        <div class="rounded-xl bg-slate-900 text-white p-5">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-blue-300 mb-2">
                Acara Resmi
            </div>
            <h1 class="text-base sm:text-lg font-bold leading-snug text-white">
                {{ $event->name }}
            </h1>
            <p class="text-xs text-slate-300 mt-2">
                {{ $event->location }}
            </p>
            <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-300">
                <span>{{ $event->event_date->format('d F Y') }}</span>
                <span>{{ number_format($event->guests_count) }} Peserta Terdaftar</span>
            </div>
        </div>

        @if($event->description)
            <div class="mt-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Deskripsi Acara</h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    {{ $event->description }}
                </p>
            </div>
        @endif
    </div>

    <!-- Kolom Kanan: Detail Pelaksanaan & Navigasi Registrasi -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">
            Detail Pelaksanaan
        </h2>

        <div class="space-y-2.5">
            <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3.5">
                <div class="text-[11px] text-slate-500">Tanggal Pelaksanaan</div>
                <div class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5">{{ $event->event_date->format('l, d F Y') }}</div>
            </div>

            @if($event->event_time)
            <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3.5">
                <div class="text-[11px] text-slate-500">Waktu</div>
                <div class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5">{{ $event->event_time }}</div>
            </div>
            @endif

            <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3.5">
                <div class="text-[11px] text-slate-500">Lokasi</div>
                <div class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5 leading-snug">{{ $event->location }}</div>
            </div>
        </div>

        <div class="pt-2 space-y-2.5">
            @if($event->is_registration_open)
                <a href="{{ route('public.register.form', $event->registration_slug) }}"
                   class="block w-full py-3 px-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xs sm:text-sm text-center transition">
                    Daftar Kehadiran (RSVP)
                </a>
            @else
                <div class="w-full py-3 px-4 rounded-xl bg-slate-100 text-slate-500 font-semibold text-xs text-center">
                    Pendaftaran Telah Ditutup
                </div>
            @endif

            <a href="{{ route('public.find-qr', $event->registration_slug) }}"
               class="block w-full py-2.5 px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs text-center transition">
                Cari / Kirim Ulang Tiket QR
            </a>
        </div>
    </div>
</div>
@endsection
