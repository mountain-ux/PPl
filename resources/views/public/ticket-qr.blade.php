@extends('layouts.app')

@section('title', 'Tiket QR Undangan — ' . $guest->name)

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <!-- Header Tiket -->
        <div class="bg-slate-900 text-white p-5">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-blue-300">
                    Tiket Undangan Digital
                </span>
                <span class="font-mono text-xs font-bold text-white bg-white/10 px-2.5 py-0.5 rounded">
                    {{ $guest->ticket_code }}
                </span>
            </div>

            <h1 class="text-base sm:text-lg font-bold leading-snug">{{ $event->name }}</h1>
            <p class="text-xs text-slate-300 mt-1">{{ $event->event_date->format('d F Y') }} {{ $event->event_time ? '• ' . $event->event_time : '' }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $event->location }}</p>
        </div>

        <!-- Identitas Tamu -->
        <div class="p-5 border-b border-slate-200 flex items-center justify-between gap-3 bg-slate-50">
            <div class="min-w-0">
                <div class="text-[10px] font-semibold uppercase text-slate-400">Nama Tamu</div>
                <div class="text-sm sm:text-base font-bold text-slate-900 truncate mt-0.5">{{ $guest->name }}</div>
                <div class="text-xs text-slate-500 truncate">{{ $guest->phone ?: $guest->email }}</div>
            </div>
            <div class="text-right shrink-0">
                @if($guest->attendance)
                    <span class="inline-block px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">Sudah Hadir</span>
                @else
                    <span class="inline-block px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold">Aktif</span>
                @endif
            </div>
        </div>

        <!-- Area QR Code -->
        <div class="p-6 text-center">
            @if($guest->hasIssuedQr())
                <div class="inline-block p-4 bg-white rounded-2xl border border-slate-200">
                    <div id="qrcodeCanvas" class="mx-auto flex items-center justify-center"></div>
                </div>

                <p class="text-xs text-slate-500 mt-3">
                    Tunjukkan kode QR ini kepada petugas di pintu masuk acara untuk proses verifikasi kehadiran.
                </p>

                <div class="mt-5 space-y-2.5">
                    <button type="button" onclick="downloadQrPng()"
                            class="w-full py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold text-xs sm:text-sm transition">
                        Unduh Gambar QR (.PNG)
                    </button>

                    <div class="grid grid-cols-2 gap-2.5">
                        @if($waShareUrl)
                            <a href="{{ $waShareUrl }}" target="_blank"
                               class="py-2.5 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold text-center">
                                Kirim ke WhatsApp
                            </a>
                        @endif
                        <button type="button" onclick="copyTicketLink()"
                                class="py-2.5 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold text-center {{ !$waShareUrl ? 'col-span-2' : '' }}">
                            Salin Tautan Tiket
                        </button>
                    </div>
                </div>
            @else
                <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-xs text-rose-800">
                    Kode QR belum terbit. Silakan hubungi panitia acara untuk menerbitkan ulang kode QR Anda.
                </div>
            @endif
        </div>
    </div>
</div>

@if($guest->hasIssuedQr())
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const qrPayload = @json($guest->qr_code);
    const ticketCode = @json($guest->ticket_code);

    new QRCode(document.getElementById("qrcodeCanvas"), {
        text: qrPayload,
        width: 200,
        height: 200,
        colorDark : "#0f172a",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.M
    });

    function copyTicketLink() {
        const url = window.location.href;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(() => alert('Tautan tiket berhasil disalin.'));
        } else {
            const tempInput = document.createElement('textarea');
            tempInput.value = url;
            tempInput.style.position = 'fixed';
            tempInput.style.opacity = '0';
            document.body.appendChild(tempInput);
            tempInput.focus();
            tempInput.select();
            try {
                document.execCommand('copy');
                alert('Tautan tiket berhasil disalin.');
            } catch (err) {
                prompt('Salin tautan tiket:', url);
            }
            document.body.removeChild(tempInput);
        }
    }

    function downloadQrPng() {
        const img = document.querySelector('#qrcodeCanvas img') || document.querySelector('#qrcodeCanvas canvas');
        if (!img) {
            alert('Silakan gunakan tangkapan layar (screenshot) untuk menyimpan tiket QR Anda.');
            return;
        }
        const dataUrl = img.src || img.toDataURL('image/png');
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = `Tiket_QR_${ticketCode}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
</script>
@endif
@endsection
