@extends('layouts.app')

@section('title', 'Manajemen Acara — TAMUIN')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Manajemen Acara</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            Kelola daftar acara, konfigurasi formulir registrasi, dan data tamu undangan.
        </p>
    </div>

    <div class="flex items-center gap-2">
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.petugas.index') }}"
               class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold">
                Kelola Petugas
            </a>
        @endif
        <a href="{{ route('events.create') }}"
           class="px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold transition">
            Buat Acara Baru
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @forelse($events as $event)
        @php
            $isActiveKiosk = ((int) $activeEventId === (int) $event->id);
            $fieldCount = is_array($event->form_fields) ? count($event->form_fields) : 0;
        @endphp
        <div class="bg-white rounded-2xl border {{ $isActiveKiosk ? 'border-blue-600 ring-1 ring-blue-600' : 'border-slate-200' }} p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <div class="flex items-center gap-1.5">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-semibold {{ $event->is_registration_open ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                            {{ $event->is_registration_open ? 'Registrasi Dibuka' : 'Registrasi Ditutup' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700">
                            {{ $event->attendances_count }}/{{ $event->guests_count }} Hadir
                        </span>
                    </div>

                    @if($isActiveKiosk)
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            Aktif di Kiosk
                        </span>
                    @endif
                </div>

                <h2 class="text-base font-bold text-slate-900 leading-snug">{{ $event->name }}</h2>
                <div class="text-xs text-slate-500 mt-1.5 space-y-0.5">
                    <div>Tanggal: {{ $event->event_date->format('d F Y') }} {{ $event->event_time ? '(' . $event->event_time . ')' : '' }}</div>
                    <div>Lokasi: {{ $event->location }}</div>
                </div>

                <div class="mt-3.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                    <span class="truncate text-slate-500 font-mono text-[11px]">/e/{{ $event->registration_slug }}</span>
                    <a href="{{ route('public.landing', $event->registration_slug) }}" target="_blank"
                       class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-blue-700 font-semibold shrink-0 hover:bg-slate-50">
                        Halaman Publik
                    </a>
                </div>
            </div>

            <div class="mt-4 pt-3.5 border-t border-slate-100 space-y-2.5">
                <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                    <a href="{{ route('events.guests.index', $event) }}"
                       class="py-2 px-1.5 sm:px-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-[11px] sm:text-xs font-semibold text-center flex items-center justify-center whitespace-nowrap">
                        Tamu ({{ $event->guests_count }})
                    </a>
                    <a href="{{ route('events.form-fields.edit', $event) }}"
                       class="py-2 px-1.5 sm:px-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-[11px] sm:text-xs font-semibold text-center flex items-center justify-center whitespace-nowrap">
                        Formulir ({{ $fieldCount }})
                    </a>
                    <form action="{{ route('events.select-active', $event) }}" method="POST" class="flex">
                        @csrf
                        <button type="submit" class="w-full py-2 px-1.5 sm:px-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] sm:text-xs font-semibold flex items-center justify-center whitespace-nowrap">
                            Buka Kiosk
                        </button>
                    </form>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-[11px] text-slate-400">Pembuat: {{ $event->creator->name ?? '-' }}</span>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('events.edit', $event) }}" class="text-xs font-semibold text-slate-600 hover:text-blue-700">Ubah</a>
                        <form action="{{ route('events.destroy', $event) }}" method="POST"
                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus acara {{ addslashes($event->name) }} beserta seluruh data tamunya?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-2 bg-white rounded-2xl border border-slate-200 p-10 text-center text-xs sm:text-sm text-slate-500">
            Belum ada acara yang terdaftar.
        </div>
    @endforelse
</div>

<div class="mt-5">
    {{ $events->links() }}
</div>
@endsection
