@extends('layouts.app')

@section('title', 'Registrasi Berhasil — ' . $guest->name)

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
    <div class="text-center pb-4 border-b border-slate-100">
        <span class="inline-block px-3 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold mb-2">
            Registrasi Berhasil
        </span>
        <h1 class="text-lg sm:text-xl font-bold text-slate-900">Data Kehadiran Tercatat</h1>
        <p class="text-xs text-slate-500 mt-1">
            Tiket QR Code Anda telah diterbitkan dan siap digunakan untuk proses check-in.
        </p>
    </div>

    <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2.5 text-xs">
        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
            <span class="text-slate-500 font-semibold uppercase">Kode Tiket</span>
            <span class="font-mono font-bold text-blue-700">{{ $guest->ticket_code }}</span>
        </div>
        <div>
            <div class="text-slate-500">Nama Lengkap</div>
            <div class="font-bold text-slate-900 text-sm mt-0.5">{{ $guest->name }}</div>
        </div>
        <div>
            <div class="text-slate-500">Acara</div>
            <div class="font-semibold text-slate-800 mt-0.5">{{ $event->name }}</div>
        </div>
        <div>
            <div class="text-slate-500">Tanggal & Lokasi</div>
            <div class="text-slate-700 mt-0.5">{{ $event->event_date->format('d F Y') }} {{ $event->event_time ? '(' . $event->event_time . ')' : '' }} &bull; {{ $event->location }}</div>
        </div>
    </div>

    <div class="mt-5 space-y-2.5">
        <a href="{{ $ticketUrl }}"
           class="block w-full py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xs sm:text-sm text-center transition">
            Lihat Tiket QR Code
        </a>

        @if($waShareUrl)
            <a href="{{ $waShareUrl }}" target="_blank"
               class="block w-full py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs text-center transition">
                Kirim Tautan Tiket ke WhatsApp
            </a>
        @endif
    </div>
</div>
@endsection
