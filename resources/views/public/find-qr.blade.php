@extends('layouts.app')

@section('title', 'Cari Tiket QR — ' . $event->name)

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
    <a href="{{ route('public.landing', $event->registration_slug) }}" class="inline-block text-xs font-semibold text-blue-700 hover:underline mb-3">
        Kembali ke Informasi Acara
    </a>

    <h1 class="text-lg font-bold text-slate-900">Cari / Kirim Ulang Tiket QR</h1>
    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
        Masukkan nomor WhatsApp, alamat email, atau kode tiket yang Anda gunakan saat melakukan registrasi pada acara <strong>{{ $event->name }}</strong>.
    </p>

    <form method="GET" action="{{ route('public.find-qr', $event->registration_slug) }}" class="mt-4 space-y-3">
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp / Email / Kode Tiket</label>
            <input type="text" name="contact" value="{{ $contact }}" required
                   placeholder="Masukkan nomor telepon, email, atau kode tiket"
                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-600 outline-none">
        </div>

        <button type="submit"
                class="w-full py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xs sm:text-sm transition">
            Cari Tiket
        </button>
    </form>

    @if($searched)
        <div class="mt-5 pt-5 border-t border-slate-100">
            @if($foundGuest)
                <div class="rounded-xl bg-emerald-50/70 border border-emerald-200 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-800">Data Tamu Ditemukan</span>
                        <span class="font-mono text-xs font-semibold text-emerald-700">{{ $foundGuest->ticket_code }}</span>
                    </div>

                    <div class="bg-white rounded-lg border border-emerald-100 p-3">
                        <div class="text-sm font-bold text-slate-900">{{ $foundGuest->name }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $foundGuest->phone ?: $foundGuest->email }}</div>
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('public.ticket.qr', [$event->registration_slug, $foundGuest->ticket_code]) }}"
                           class="block w-full py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xs text-center">
                            Buka Tiket QR Code
                        </a>

                        @if($waShareUrl)
                            <a href="{{ $waShareUrl }}" target="_blank"
                               class="block w-full py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs text-center">
                                Kirim ke WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 space-y-2 text-xs">
                    <div class="font-bold text-slate-900">Data Tidak Ditemukan</div>
                    <p class="text-slate-600 leading-relaxed">
                        Data dengan kontak <strong>"{{ $contact }}"</strong> belum terdaftar pada acara ini.
                    </p>
                    @if($event->is_registration_open)
                        <div class="pt-1">
                            <a href="{{ route('public.register.form', $event->registration_slug) }}"
                               class="inline-block px-4 py-2 rounded-lg bg-blue-700 text-white font-semibold hover:bg-blue-800">
                                Daftar Baru
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
