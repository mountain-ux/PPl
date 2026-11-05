@extends('layouts.app')

@section('title', 'Impor Data Tamu — ' . $event->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-4">
        <div class="flex items-center justify-between text-xs mb-2">
            <a href="{{ route('events.guests.index', $event) }}" class="font-semibold text-blue-700 hover:underline">
                Kembali ke Data Tamu
            </a>
            <span class="text-slate-500">{{ $event->name }}</span>
        </div>

        <h1 class="text-lg sm:text-xl font-bold text-slate-900">Impor Data Tamu Massal</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            Unggah berkas Excel (<code>.xlsx</code>) atau CSV (<code>.csv</code>) untuk menambahkan daftar tamu secara sekaligus.
        </p>

        <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-3.5 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900">Format Template Impor</div>
                <div class="text-[11px] text-slate-500 truncate">Sesuai dengan kolom standar dan kolom tambahan acara ini</div>
            </div>
            <a href="{{ route('events.guests.import.template', $event) }}"
               class="px-3.5 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50 shrink-0">
                Unduh Template CSV
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-4">
        <form action="{{ route('events.guests.import.preview', $event) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <label class="block text-xs font-semibold text-slate-700">Pilih Berkas (.xlsx atau .csv)</label>
            <input type="file" name="import_file" accept=".csv,.txt,.xlsx" required
                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs text-slate-700">
            <button type="submit"
                    class="w-full py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs sm:text-sm font-semibold transition">
                Periksa & Validasi Berkas
            </button>
        </form>
    </div>

    @if($preview)
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-3.5">
                <div class="text-[10px] font-semibold uppercase text-slate-500">Total Baris</div>
                <div class="text-xl font-bold text-slate-900 mt-1">{{ $preview['total_rows'] }}</div>
            </div>

            <div class="bg-white rounded-2xl border border-emerald-200 p-3.5">
                <div class="text-[10px] font-semibold uppercase text-emerald-700">Data Valid</div>
                <div class="text-xl font-bold text-emerald-600 mt-1">{{ $preview['valid_count'] }}</div>
            </div>

            <div class="bg-white rounded-2xl border {{ $preview['invalid_count'] > 0 ? 'border-rose-200' : 'border-slate-200' }} p-3.5">
                <div class="text-[10px] font-semibold uppercase {{ $preview['invalid_count'] > 0 ? 'text-rose-700' : 'text-slate-500' }}">Perlu Perbaikan</div>
                <div class="text-xl font-bold {{ $preview['invalid_count'] > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-1">{{ $preview['invalid_count'] }}</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900">Pratinjau Data Impor</h2>
                <span class="text-[11px] text-slate-500">{{ $preview['file_name'] }} ({{ $preview['file_size_kb'] }} KB)</span>
            </div>

            <div class="flex items-center gap-2 mb-4">
                <button type="button" onclick="filterPreviewCards('all')"
                        class="px-3 py-1 rounded-lg text-xs font-semibold bg-slate-900 text-white">
                    Semua ({{ $preview['total_rows'] }})
                </button>
                <button type="button" onclick="filterPreviewCards('valid')"
                        class="px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
                    Valid ({{ $preview['valid_count'] }})
                </button>
                <button type="button" onclick="filterPreviewCards('invalid')"
                        class="px-3 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700">
                    Bermasalah ({{ $preview['invalid_count'] }})
                </button>
            </div>

            <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                @foreach($preview['invalid_rows'] as $bad)
                    <div class="preview-card preview-invalid rounded-xl border border-rose-200 bg-rose-50/40 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-[10px] font-semibold text-rose-700">Baris #{{ $bad['row_number'] }}</span>
                                <div class="text-xs sm:text-sm font-bold text-slate-900">{{ $bad['name'] ?: '(Nama Kosong)' }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 text-[10px] font-semibold">
                                {{ $bad['errors'][0] ?? 'Format tidak sesuai' }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-slate-600">
                            {{ $bad['phone'] ?: '-' }} &bull; {{ $bad['email'] ?: '-' }}
                        </div>
                    </div>
                @endforeach

                @foreach($preview['valid_rows'] as $ok)
                    <div class="preview-card preview-valid rounded-xl border border-slate-200 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-[10px] text-slate-400">Baris #{{ $ok['row_number'] }}</span>
                                <div class="text-xs sm:text-sm font-bold text-slate-900">{{ $ok['name'] }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold">
                                Valid
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $ok['phone'] ?: '-' }} &bull; {{ $ok['email'] ?: '-' }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-3 gap-2.5">
                <form action="{{ route('events.guests.import.reset', $event) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Batal / Reset
                    </button>
                </form>

                @if($preview['valid_count'] > 0)
                    <form action="{{ route('events.guests.import.confirm', $event) }}" method="POST" class="col-span-2">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs sm:text-sm font-semibold">
                            Simpan {{ $preview['valid_count'] }} Data Valid
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>

<script>
    function filterPreviewCards(mode) {
        document.querySelectorAll('.preview-card').forEach((el) => {
            if (mode === 'all') el.classList.remove('hidden');
            else if (mode === 'valid') el.classList.toggle('hidden', !el.classList.contains('preview-valid'));
            else if (mode === 'invalid') el.classList.toggle('hidden', !el.classList.contains('preview-invalid'));
        });
    }
</script>
@endsection
