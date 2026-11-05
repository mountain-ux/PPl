@extends('layouts.app')

@section('title', 'Laporan Kehadiran — TAMUIN')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <span class="text-xs font-semibold text-blue-700">Rekapitulasi & Ekspor Laporan</span>

        <form method="GET" action="{{ route('admin.reports.index') }}">
            <select name="event_id" onchange="this.form.submit()"
                    class="max-w-[190px] sm:max-w-xs truncate rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 outline-none">
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}" {{ ($activeEvent && $activeEvent->id === $ev->id) ? 'selected' : '' }}>
                        {{ $ev->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <h1 class="text-lg sm:text-xl font-bold text-slate-900">
        {{ $activeEvent ? $activeEvent->name : 'Laporan Kehadiran Acara' }}
    </h1>
    <p class="text-xs text-slate-500 mt-0.5">
        Data kehadiran yang telah disinkronisasi dari perangkat kiosk ke server pusat.
    </p>

    @if($activeEvent)
        <div class="mt-4 grid grid-cols-2 gap-2.5 sm:gap-3">
            <a href="{{ route('admin.reports.export-excel', [$activeEvent, 'status' => $statusFilter]) }}"
               class="py-2.5 px-3 sm:px-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs sm:text-sm font-semibold text-center transition whitespace-nowrap">
                Export Excel<span class="hidden sm:inline"> (.xlsx)</span>
            </a>

            <a href="{{ route('admin.reports.export-pdf', [$activeEvent, 'status' => $statusFilter]) }}" target="_blank"
               class="py-2.5 px-3 sm:px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 text-xs sm:text-sm font-semibold text-center transition whitespace-nowrap">
                Export PDF
            </a>
        </div>
    @endif
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Total Tamu</div>
        <div class="text-2xl font-bold text-slate-900 mt-1">{{ $report['total_guests'] }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Total Hadir</div>
        <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $report['checked_in_count'] }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Belum Hadir</div>
        <div class="text-2xl font-bold text-amber-600 mt-1">{{ $report['pending_count'] }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Persentase Kehadiran</div>
        <div class="text-2xl font-bold text-blue-700 mt-1">{{ $report['attendance_rate'] }}%</div>
    </div>
</div>

<!-- Daftar Rekapitulasi Presensi -->
<div class="bg-white rounded-2xl border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Daftar Kehadiran Peserta</h2>
            <p class="text-[11px] text-slate-500">Menampilkan {{ $report['guests']->count() }} data tamu</p>
        </div>

        @if($activeEvent)
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex w-full sm:w-auto gap-2">
            <input type="hidden" name="event_id" value="{{ $activeEvent->id }}">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, email, atau kode tiket..."
                   class="flex-1 w-full sm:w-64 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs focus:border-blue-600 outline-none">
            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shrink-0">Cari</button>
        </form>
        @endif
    </div>

    @if($activeEvent)
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar mb-4">
        <a href="{{ route('admin.reports.index', ['event_id' => $activeEvent->id, 'status' => 'all', 'q' => $search]) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $statusFilter === 'all' ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-600' }}">
            Semua ({{ $report['total_guests'] }})
        </a>
        <a href="{{ route('admin.reports.index', ['event_id' => $activeEvent->id, 'status' => 'checked_in', 'q' => $search]) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $statusFilter === 'checked_in' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">
            Sudah Hadir ({{ $report['checked_in_count'] }})
        </a>
        <a href="{{ route('admin.reports.index', ['event_id' => $activeEvent->id, 'status' => 'pending', 'q' => $search]) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600' }}">
            Belum Hadir ({{ $report['pending_count'] }})
        </a>
    </div>
    @endif

    <div class="space-y-2.5">
        @forelse($report['guests'] as $guest)
            @php
                $initials = collect(explode(' ', $guest->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                $cat = $guest->custom_data['kategori_tamu'] ?? ucfirst($guest->source);
            @endphp
            <div class="rounded-xl border border-slate-200 p-3.5 flex items-center justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 border border-slate-200">
                        {{ $initials }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $guest->name }}</div>
                        <div class="text-[11px] text-slate-500 truncate">{{ $guest->email ?: $guest->phone }}</div>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-semibold">{{ $cat }}</span>
                            <span class="font-mono text-[10px] text-slate-500">{{ $guest->ticket_code }}</span>
                        </div>
                    </div>
                </div>

                <div class="text-right shrink-0">
                    @if($guest->attendance)
                        <span class="inline-block px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[11px] font-semibold border border-emerald-200">
                            Hadir
                        </span>
                        <div class="text-[10px] text-slate-500 mt-1">
                            {{ $guest->attendance->checked_in_at->format('H:i:s') }} WITA
                        </div>
                    @else
                        <span class="inline-block px-2.5 py-0.5 rounded-md bg-amber-50 text-amber-700 text-[11px] font-semibold border border-amber-200">
                            Belum Hadir
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-8 text-center text-xs text-slate-500">Tidak ada data presensi untuk kriteria ini.</div>
        @endforelse
    </div>
</div>
@endsection
