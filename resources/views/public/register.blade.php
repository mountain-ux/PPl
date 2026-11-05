@extends('layouts.app')

@section('title', 'Formulir Registrasi Tamu — ' . $event->name)

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <a href="{{ route('public.landing', $event->registration_slug) }}" class="inline-block text-xs font-semibold text-blue-700 hover:underline mb-3">
                Kembali ke Informasi Acara
            </a>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5">
                <h1 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">{{ $event->name }}</h1>
                <p class="text-xs text-slate-500 mt-1">{{ $event->event_date->format('d F Y') }} &bull; {{ $event->location }}</p>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <h2 class="text-base font-bold text-slate-900">Formulir Registrasi Tamu</h2>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">
                Lengkapi data berikut untuk memperoleh kode QR undangan.
            </p>

            <form action="{{ route('public.register.submit', $event->registration_slug) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="Masukkan nama lengkap"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp / Telepon Aktif <span class="text-rose-500">*</span></label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required
                           placeholder="Contoh: 081234567890"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
                    <p class="text-[11px] text-slate-500 mt-1">
                        Digunakan untuk pengiriman dan pencarian ulang tiket QR Anda.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email (Opsional)</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="nama@email.com"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
                </div>

                @foreach($customFields as $field)
                    @php
                        $fName = $field['name'];
                        $fLabel = $field['label'];
                        $fType = $field['type'] ?? 'text';
                        $fReq = !empty($field['is_required']);
                        $options = $field['options'] ?? [];
                    @endphp
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            {{ $fLabel }} {!! $fReq ? '<span class="text-rose-500">*</span>' : '' !!}
                        </label>

                        @if($fType === 'select')
                            <select name="custom_data[{{ $fName }}]" {{ $fReq ? 'required' : '' }}
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
                                <option value="">-- Pilih {{ $fLabel }} --</option>
                                @foreach($options as $opt)
                                    <option value="{{ $opt }}" {{ old("custom_data.{$fName}") === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif($fType === 'textarea')
                            <textarea name="custom_data[{{ $fName }}]" rows="2" {{ $fReq ? 'required' : '' }}
                                      class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">{{ old("custom_data.{$fName}") }}</textarea>
                        @else
                            <input type="{{ $fType === 'number' ? 'number' : 'text' }}"
                                   name="custom_data[{{ $fName }}]"
                                   value="{{ old("custom_data.{$fName}") }}"
                                   {{ $fReq ? 'required' : '' }}
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
                        @endif
                    </div>
                @endforeach

                <div class="pt-2">
                    <button type="submit"
                            class="w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 text-sm transition">
                        Daftar & Dapatkan Tiket QR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
