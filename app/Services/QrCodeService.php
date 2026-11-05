<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Str;

class QrCodeService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = (string) config('app.key', 'tamuin-default-secret-key');
    }

    /**
     * Buat kode tiket alfanumerik unik (mis. TMN-2026-84921)
     * Digunakan juga sebagai alternatif input manual di kiosk jika kamera buram/rusak (UC-08 Alternative Flow)
     */
    public function generateUniqueTicketCode(Event $event): string
    {
        $year = $event->event_date ? $event->event_date->format('Y') : date('Y');

        do {
            $randomPart = strtoupper(Str::random(3)) . random_int(10, 99);
            $ticketCode = "TMN-{$year}-{$randomPart}";
        } while (Guest::where('ticket_code', $ticketCode)->exists());

        return $ticketCode;
    }

    /**
     * Menghasilkan payload QR Code unik bertanda tangan HMAC-SHA256 (FR-03, UC-03, NFR-04).
     * QR terikat pada kombinasi Tamu dan Event (bukan tamu secara global).
     * Format: TMN1|{event_id}|{guest_id}|{ticket_code}|{signature}
     */
    public function generate(Guest $guest): string
    {
        if (empty($guest->ticket_code)) {
            $guest->ticket_code = $this->generateUniqueTicketCode($guest->event);
            $guest->saveQuietly();
        }

        $attempt = 0;
        do {
            $nonce = $attempt > 0 ? '-' . Str::random(4) : '';
            $ticketRef = $guest->ticket_code . $nonce;
            $dataToSign = "TMN1|{$guest->event_id}|{$guest->id}|{$ticketRef}";
            $signature = substr(hash_hmac('sha256', $dataToSign, $this->secretKey), 0, 16);
            $payload = "{$dataToSign}|{$signature}";
            $attempt++;
        } while (
            Guest::where('qr_code', $payload)
                ->where('id', '!=', $guest->id)
                ->exists()
        );

        return $payload;
    }

    /**
     * Verifikasi keabsahan tanda tangan HMAC pada payload QR Code (SDD Bab III.J & Bab V Security)
     */
    public function verify(string $payload, ?int $expectedEventId = null): ?array
    {
        $parts = explode('|', trim($payload));
        if (count($parts) !== 5 || $parts[0] !== 'TMN1') {
            return null;
        }

        [$prefix, $eventId, $guestId, $ticketCode, $providedSignature] = $parts;

        if (!ctype_digit((string) $eventId) || !ctype_digit((string) $guestId)) {
            return null;
        }

        $dataToSign = "{$prefix}|{$eventId}|{$guestId}|{$ticketCode}";
        $expectedSignature = substr(hash_hmac('sha256', $dataToSign, $this->secretKey), 0, 16);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        if ($expectedEventId !== null && (int) $eventId !== (int) $expectedEventId) {
            return null;
        }

        return [
            'event_id' => (int) $eventId,
            'guest_id' => (int) $guestId,
            'ticket_code' => $ticketCode,
            'signature' => $providedSignature,
            'payload' => $payload,
        ];
    }
}
