@extends('layouts.app')

@section('title', ($isEdit ? 'Ubah Acara' : 'Buat Acara Baru') . ' — TAMUIN')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('events.index') }}" class="text-xs font-semibold text-blue-700 hover:underline">Kembali ke Manajemen Acara</a>
        <h1 class="text-xl font-bold text-slate-900 mt-1">{{ $isEdit ? 'Ubah Data Acara' : 'Buat Acara Baru' }}</h1>
        <p class="text-xs text-slate-500">Lengkapi rincian pelaksanaan acara di bawah ini.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <form action="{{ $isEdit ? route('events.update', $event) : route('events.store') }}" method="POST" class="space-y-4">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Acara *</label>
                <input type="text" name="name" value="{{ old('name', $event->name) }}" required
                       placeholder="Masukkan nama acara"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pelaksanaan *</label>
                    <input type="date" name="event_date"
                           value="{{ old('event_date', optional($event->event_date)->format('Y-m-d')) }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu Pelaksanaan (Opsional)</label>
                    <input type="text" name="event_time" value="{{ old('event_time', $event->event_time) }}"
                           placeholder="Contoh: 08:00 - 15:30 WITA"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Acara *</label>
                <input type="text" name="location" value="{{ old('location', $event->location) }}" required
                       placeholder="Masukkan lokasi atau gedung pelaksanaan acara"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Acara</label>
                <textarea name="description" rows="3"
                          placeholder="Keterangan singkat mengenai acara..."
                          class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">{{ old('description', $event->description) }}</textarea>
            </div>

            <div class="pt-1">
                <label class="inline-flex items-center gap-2.5 cursor-pointer text-xs sm:text-sm text-slate-700">
                    <input type="checkbox" name="is_registration_open" value="1"
                           {{ old('is_registration_open', $event->is_registration_open) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Buka formulir registrasi publik untuk acara ini</span>
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                <a href="{{ route('events.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan & Atur Formulir' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
