@extends('layouts.app')

@section('title', 'Data Tamu — ' . $event->name)

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-5 mb-4">
    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
        <a href="{{ route('events.index') }}" class="font-semibold text-blue-700 hover:underline">Daftar Acara</a>
        <span>{{ $event->event_date->format('d F Y') }}</span>
    </div>

    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900">{{ $event->name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pengelolaan daftar tamu undangan dan status presensi</p>
        </div>
        <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-semibold">
            {{ $stats['total'] }} Tamu Terdaftar
        </span>
    </div>

    <div class="mt-4 space-y-2.5">
        <button type="button" onclick="document.getElementById('modalAddGuest').classList.remove('hidden')"
                class="w-full py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs sm:text-sm font-semibold transition">
            Tambah Tamu Baru
        </button>

        <div class="grid grid-cols-2 gap-2.5">
            <a href="{{ route('events.guests.import.form', $event) }}"
               class="py-2 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold text-center">
                Impor Data (.xlsx / .csv)
            </a>
            <a href="{{ route('events.guests.export-csv', $event) }}"
               class="py-2 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold text-center">
                Ekspor CSV
            </a>
        </div>
    </div>
</div>

<!-- Ringkasan Statistik -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-3.5">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Total Terdaftar</div>
        <div class="text-xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-3.5">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Sudah Hadir</div>
        <div class="text-xl font-bold text-emerald-600 mt-1">{{ $stats['checked_in'] }} <span class="text-xs font-normal text-slate-500">({{ $stats['rate'] }}%)</span></div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-3.5">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Belum Hadir</div>
        <div class="text-xl font-bold text-amber-600 mt-1">{{ $stats['pending'] }}</div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-3.5">
        <div class="text-[10px] font-semibold uppercase text-slate-500">Status Kode QR</div>
        @if($stats['qr_missing'] > 0)
            <div class="text-xl font-bold text-rose-600 mt-1">{{ $stats['qr_missing'] }} <span class="text-xs font-normal">Belum Terbit</span></div>
        @else
            <div class="text-xl font-bold text-slate-900 mt-1">Lengkap</div>
        @endif
    </div>
</div>

<!-- Pencarian & Penyaringan -->
<div class="bg-white rounded-2xl border border-slate-200 p-4 mb-4 space-y-3">
    <form method="GET" action="{{ route('events.guests.index', $event) }}" class="flex gap-2">
        <input type="hidden" name="status" value="{{ $statusFilter }}">
        <input type="text" name="q" value="{{ $search }}"
               placeholder="Cari nama tamu, nomor telepon, email, atau kode tiket..."
               class="flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs sm:text-sm focus:border-blue-600 outline-none">
        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold">
            Cari
        </button>
    </form>

    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
        <a href="{{ route('events.guests.index', [$event, 'status' => 'all', 'q' => $search]) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $statusFilter === 'all' ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Semua ({{ $stats['total'] }})
        </a>
        <a href="{{ route('events.guests.index', [$event, 'status' => 'checked_in', 'q' => $search]) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $statusFilter === 'checked_in' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Sudah Hadir ({{ $stats['checked_in'] }})
        </a>
        <a href="{{ route('events.guests.index', [$event, 'status' => 'pending', 'q' => $search]) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Belum Hadir ({{ $stats['pending'] }})
        </a>
    </div>
</div>

<!-- Daftar Tamu -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
    @forelse($guests as $guest)
        @php
            $initials = collect(explode(' ', $guest->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
            $categoryLabel = $guest->custom_data['kategori_tamu']
                ?? $guest->custom_data['asal_instansi']
                ?? ucfirst($guest->source);
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 border border-slate-200">
                            {{ $initials ?: 'TM' }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-900 leading-snug">{{ $guest->name }}</h3>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-semibold">
                                    {{ $categoryLabel }}
                                </span>
                                <span class="font-mono text-[11px] text-slate-500">
                                    {{ $guest->ticket_code }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($guest->hasIssuedQr())
                            <a href="{{ route('public.ticket.qr', [$event->registration_slug, $guest->ticket_code]) }}" target="_blank"
                               class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-[11px] font-semibold">
                                Tiket QR
                            </a>
                        @else
                            <form action="{{ route('events.guests.regenerate-qr', [$event, $guest]) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-semibold">
                                    Terbitkan QR
                                </button>
                            </form>
                        @endif

                        <button type="button"
                                data-guest="{{ json_encode($guest) }}"
                                onclick="openEditModal(JSON.parse(this.dataset.guest))"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-[11px] font-semibold">
                            Ubah
                        </button>

                        <form action="{{ route('events.guests.destroy', [$event, $guest]) }}" method="POST"
                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus data tamu {{ addslashes($guest->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-[11px] font-semibold">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-3 rounded-xl bg-slate-50 border border-slate-200/70 p-3 space-y-1 text-xs text-slate-600">
                    <div class="truncate"><span class="text-slate-400">Email:</span> {{ $guest->email ?: '-' }}</div>
                    <div><span class="text-slate-400">Telepon/WA:</span> {{ $guest->phone ?: '-' }}</div>
                </div>
            </div>

            <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                @if($guest->attendance)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Sudah Hadir
                    </span>
                    <span class="text-[11px] text-slate-500">
                        {{ $guest->attendance->checked_in_at->format('H:i') }} WITA
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Belum Hadir
                    </span>

                    <form action="{{ route('events.guests.manual-checkin', [$event, $guest]) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold transition">
                            Catat Hadir
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-2 bg-white rounded-2xl border border-slate-200 p-10 text-center text-xs sm:text-sm text-slate-500">
            Tidak ada data tamu yang ditemukan.
        </div>
    @endforelse
</div>

<div class="mt-5">
    {{ $guests->links() }}
</div>

<!-- Modal Tambah Tamu -->
<div id="modalAddGuest" class="hidden fixed inset-0 z-50 bg-slate-900/50 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-2xl sm:rounded-2xl max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-slate-900">Tambah Data Tamu</h2>
            <button type="button" onclick="document.getElementById('modalAddGuest').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form action="{{ route('events.guests.store', $event) }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required placeholder="Masukkan nama lengkap tamu"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp / Telepon *</label>
                    <input type="text" name="phone" required placeholder="081234567890"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" placeholder="nama@email.com"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            @foreach($event->form_fields ?? [] as $field)
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        {{ $field['label'] }} {!! !empty($field['is_required']) ? '<span class="text-rose-500">*</span>' : '' !!}
                    </label>
                    @if(($field['type'] ?? 'text') === 'select')
                        <select name="custom_data[{{ $field['name'] }}]" {{ !empty($field['is_required']) ? 'required' : '' }}
                                class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                            <option value="">-- Pilih {{ $field['label'] }} --</option>
                            @foreach($field['options'] ?? [] as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif(($field['type'] ?? 'text') === 'textarea')
                        <textarea name="custom_data[{{ $field['name'] }}]" rows="2" {{ !empty($field['is_required']) ? 'required' : '' }}
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm"></textarea>
                    @else
                        <input type="{{ ($field['type'] ?? 'text') === 'number' ? 'number' : 'text' }}"
                               name="custom_data[{{ $field['name'] }}]" {{ !empty($field['is_required']) ? 'required' : '' }}
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    @endif
                </div>
            @endforeach

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddGuest').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-700 text-white text-xs font-semibold hover:bg-blue-800">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Data Tamu -->
<div id="modalEditGuest" class="hidden fixed inset-0 z-50 bg-slate-900/50 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-2xl sm:rounded-2xl max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-slate-900">Ubah Data Tamu</h2>
            <button type="button" onclick="document.getElementById('modalEditGuest').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form id="formEditGuest" method="POST" class="space-y-3.5">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="name" id="edit_name" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp / Telepon</label>
                    <input type="text" name="phone" id="edit_phone" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" id="edit_email" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            @foreach($event->form_fields ?? [] as $field)
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        {{ $field['label'] }} {!! !empty($field['is_required']) ? '<span class="text-rose-500">*</span>' : '' !!}
                    </label>
                    @if(($field['type'] ?? 'text') === 'select')
                        <select name="custom_data[{{ $field['name'] }}]" id="edit_custom_{{ $field['name'] }}"
                                {{ !empty($field['is_required']) ? 'required' : '' }}
                                class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                            <option value="">-- Pilih {{ $field['label'] }} --</option>
                            @foreach($field['options'] ?? [] as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif(($field['type'] ?? 'text') === 'textarea')
                        <textarea name="custom_data[{{ $field['name'] }}]" id="edit_custom_{{ $field['name'] }}" rows="2"
                                  {{ !empty($field['is_required']) ? 'required' : '' }}
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm"></textarea>
                    @else
                        <input type="{{ ($field['type'] ?? 'text') === 'number' ? 'number' : 'text' }}"
                               name="custom_data[{{ $field['name'] }}]" id="edit_custom_{{ $field['name'] }}"
                               {{ !empty($field['is_required']) ? 'required' : '' }}
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    @endif
                </div>
            @endforeach

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalEditGuest').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-700 text-white text-xs font-semibold hover:bg-blue-800">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const baseUpdateUrl = "{{ url('events/' . $event->id . '/guests') }}";

    function openEditModal(guest) {
        document.getElementById('formEditGuest').action = `${baseUpdateUrl}/${guest.id}`;
        document.getElementById('edit_name').value = guest.name || '';
        document.getElementById('edit_phone').value = guest.phone || '';
        document.getElementById('edit_email').value = guest.email || '';

        const custom = guest.custom_data || {};
        @foreach($event->form_fields ?? [] as $field)
            const el_{{ $loop->index }} = document.getElementById('edit_custom_{{ $field['name'] }}');
            if (el_{{ $loop->index }}) {
                el_{{ $loop->index }}.value = custom['{{ $field['name'] }}'] || '';
            }
        @endforeach

        document.getElementById('modalEditGuest').classList.remove('hidden');
    }
</script>
@endsection
