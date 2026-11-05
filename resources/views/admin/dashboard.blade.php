@extends('layouts.app')

@section('title', 'Dashboard Kehadiran — TAMUIN')

@section('content')
<!-- Header Acara Aktif -->
<div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> Ringkasan Acara
        </span>

        <form method="GET" action="{{ route('admin.dashboard') }}">
            <select name="event_id" onchange="this.form.submit()"
                    class="max-w-[190px] sm:max-w-xs truncate rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 focus:border-blue-600 outline-none">
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}" {{ ($activeEvent && $activeEvent->id === $ev->id) ? 'selected' : '' }}>
                        {{ $ev->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($activeEvent)
        <h1 class="text-lg sm:text-xl font-bold text-slate-900 leading-snug">
            {{ $activeEvent->name }}
        </h1>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
            <span>Tanggal: <strong class="font-medium text-slate-700">{{ $activeEvent->event_date->format('d F Y') }}</strong> {{ $activeEvent->event_time ? '(' . $activeEvent->event_time . ')' : '' }}</span>
            <span>Lokasi: <strong class="font-medium text-slate-700">{{ $activeEvent->location }}</strong></span>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3">
            <a href="{{ route('admin.reports.export-excel', $activeEvent) }}"
               class="py-2.5 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold text-center transition">
                Unduh Rekap Excel
            </a>

            <a href="{{ route('kiosk.index', ['event_id' => $activeEvent->id]) }}"
               class="py-2.5 px-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold text-center transition">
                Buka Kiosk Check-in
            </a>
        </div>
    @else
        <div class="text-sm text-slate-500 py-4">
            Belum ada data acara. <a href="{{ route('events.create') }}" class="text-blue-700 font-semibold underline">Buat Acara Baru</a>
        </div>
    @endif
</div>

<!-- Grid 2x2 Statistik Utama -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Tamu</div>
        <div class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">{{ number_format($report['total_guests']) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Terdaftar pada sistem</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Sudah Hadir</div>
        <div class="text-2xl sm:text-3xl font-bold text-emerald-600 mt-1">{{ number_format($report['checked_in_count']) }}</div>
        <div class="text-[11px] text-emerald-700 font-medium mt-1">{{ $report['attendance_rate'] }}% dari total tamu</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Belum Hadir</div>
        <div class="text-2xl sm:text-3xl font-bold text-amber-600 mt-1">{{ number_format($report['pending_count']) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Menunggu check-in</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Petugas Aktif</div>
        <div class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">{{ number_format($totalPetugas) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Total {{ $events->count() }} acara terdaftar</div>
    </div>
</div>

<!-- Grafik Kehadiran & Progres Kategori -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-4">
    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Distribusi Waktu Kehadiran</h2>
                <p class="text-[11px] text-slate-500">Rekapitulasi waktu check-in tamu</p>
            </div>
            <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-[11px] font-semibold">
                Waktu Puncak: {{ $report['peak_slot'] }} ({{ $report['peak_count'] }} tamu)
            </span>
        </div>

        <div class="relative pt-3 pb-1">
            <svg viewBox="0 0 400 110" class="w-full h-28 overflow-visible">
                <defs>
                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#1d4ed8" stop-opacity="0.20"/>
                        <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                <line x1="0" y1="95" x2="400" y2="95" stroke="#e2e8f0" stroke-width="1"/>
                <line x1="0" y1="55" x2="400" y2="55" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4 4"/>
                <path d="M 10 90 C 70 85, 110 70, 160 35 C 195 15, 225 18, 260 50 C 300 80, 345 84, 390 88 L 390 95 L 10 95 Z" fill="url(#chartGrad)"/>
                <path d="M 10 90 C 70 85, 110 70, 160 35 C 195 15, 225 18, 260 50 C 300 80, 345 84, 390 88" fill="none" stroke="#1d4ed8" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="195" cy="20" r="4" fill="#1d4ed8" stroke="#ffffff" stroke-width="2"/>
            </svg>

            <div class="grid grid-cols-5 text-center text-[10px] font-medium text-slate-500 mt-1">
                <span>07:30</span>
                <span>08:00</span>
                <span class="text-blue-700 font-semibold">09:00</span>
                <span>10:00</span>
                <span>11:00+</span>
            </div>
        </div>
    </div>

    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-900">Progres per Kategori</h2>
            <span class="text-[11px] font-medium text-slate-500">{{ count($report['categories']) }} Kategori</span>
        </div>

        <div class="space-y-3.5">
            @forelse($report['categories'] as $idx => $cat)
                @php
                    $barColors = ['bg-blue-700', 'bg-emerald-600', 'bg-amber-500', 'bg-slate-600'];
                    $barClass = $barColors[$idx % count($barColors)];
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700">{{ $cat['label'] }}</span>
                        <span class="font-semibold text-slate-900">
                            {{ $cat['checked_in'] }}/{{ $cat['total'] }}
                            <span class="text-[11px] font-normal text-slate-500 ml-1">({{ $cat['rate'] }}%)</span>
                        </span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full {{ $barClass }} rounded-full" style="width: {{ min(100, $cat['rate']) }}%"></div>
                    </div>
                </div>
            @empty
                <div class="text-xs text-slate-400 py-6 text-center">Belum ada data kategori tamu.</div>
            @endforelse
        </div>
    </div>
</div>

<!-- Aktivitas Check-in Terbaru -->
<div class="bg-white rounded-2xl border border-slate-200 p-5">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-bold text-slate-900">Riwayat Check-in Terbaru</h2>
        @if($activeEvent)
            <a href="{{ route('admin.reports.index', ['event_id' => $activeEvent->id]) }}" class="text-xs font-semibold text-blue-700 hover:underline">
                Lihat Seluruh Laporan
            </a>
        @endif
    </div>

    <div class="divide-y divide-slate-100">
        @forelse($recentAttendances as $att)
            @php
                $gName = $att->guest->name ?? 'Tamu';
                $initials = collect(explode(' ', $gName))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                $catLabel = $att->guest->custom_data['kategori_tamu'] ?? ucfirst($att->guest->source ?? 'Tamu');
            @endphp
            <div class="py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 border border-slate-200">
                        {{ $initials }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs sm:text-sm font-semibold text-slate-900 truncate">{{ $gName }}</div>
                        <div class="text-[11px] text-slate-500">
                            {{ $att->checked_in_at->format('d/m/Y H:i:s') }} &bull; Petugas: {{ $att->scanner->name ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="text-right shrink-0">
                    <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-semibold">
                        Hadir
                    </span>
                    <div class="text-[10px] text-slate-500 mt-0.5">{{ Str::limit($catLabel, 20) }}</div>
                </div>
            </div>
        @empty
            <div class="py-8 text-center text-xs text-slate-500">
                Belum ada data kehadiran yang tersinkronisasi untuk acara ini.
            </div>
        @endforelse
    </div>
</div>
@endsection
