<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistrationService
{
    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Susun aturan validasi dinamis berdasarkan konfigurasi form_fields pada Event (FR-18)
     */
    public function buildValidationRules(Event $event, ?int $ignoreGuestId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'custom_data' => ['nullable', 'array'],
        ];

        foreach ($event->form_fields ?? [] as $field) {
            $key = $field['name'] ?? null;
            if (!$key) {
                continue;
            }

            $fieldRules = [];
            $fieldRules[] = !empty($field['is_required']) ? 'required' : 'nullable';

            $type = $field['type'] ?? 'text';
            if ($type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($type === 'select' && !empty($field['options'])) {
                // Gunakan Rule::in() berbentuk array agar opsi yang mengandung tanda koma (mis. "Ya, Pasti Hadir") tidak terpecah
                $fieldRules[] = Rule::in($field['options']);
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:500';
            }

            $rules["custom_data.{$key}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Simpan data tamu baru dan terbitkan QR Code unik (UC-02, UC-03, UC-05)
     * Mendukung Exception Flow UC-03: Jika pembuatan QR gagal, tandai sebagai "QR belum terbit" (qr_code = null).
     */
    public function registerGuest(Event $event, array $validatedData, string $source = 'mandiri', bool $ForceQrFailSimulation = false): Guest
    {
        // Cek duplikasi nomor HP atau Email pada Event yang sama
        $normalizedPhone = $this->normalizePhone($validatedData['phone'] ?? '');
        $email = !empty($validatedData['email']) ? strtolower(trim($validatedData['email'])) : null;

        if ($normalizedPhone !== '') {
            $existingByPhone = Guest::where('event_id', $event->id)
                ->where('phone', $normalizedPhone)
                ->first();

            if ($existingByPhone) {
                throw ValidationException::withMessages([
                    'phone' => "Nomor WhatsApp/HP ini sudah terdaftar pada acara ini atas nama {$existingByPhone->name} (Tiket: {$existingByPhone->ticket_code}). Gunakan menu Cari / Kirim Ulang QR.",
                ]);
            }
        }

        if ($email !== null) {
            $existingByEmail = Guest::where('event_id', $event->id)
                ->where('email', $email)
                ->first();

            if ($existingByEmail) {
                throw ValidationException::withMessages([
                    'email' => "Alamat email ini sudah terdaftar pada acara ini atas nama {$existingByEmail->name}.",
                ]);
            }
        }

        return DB::transaction(function () use ($event, $validatedData, $normalizedPhone, $email, $source, $ForceQrFailSimulation) {
            $ticketCode = $this->qrCodeService->generateUniqueTicketCode($event);

            $guest = Guest::create([
                'event_id' => $event->id,
                'name' => trim($validatedData['name']),
                'phone' => $normalizedPhone ?: null,
                'email' => $email,
                'custom_data' => $validatedData['custom_data'] ?? [],
                'ticket_code' => $ticketCode,
                'qr_code' => null,
                'source' => $source,
                'registered_at' => now(),
            ]);

            // UC-03: Generate QR Code unik (jika gagal, biarkan qr_code = null sebagai penanda "QR belum terbit")
            try {
                if ($ForceQrFailSimulation) {
                    throw new \RuntimeException('Simulasi kegagalan penerbitan QR');
                }
                $qrPayload = $this->qrCodeService->generate($guest);
                $guest->update(['qr_code' => $qrPayload]);
            } catch (Throwable $e) {
                report($e);
            }

            return $guest->fresh();
        });
    }

    /**
     * Cari tamu terdaftar pada suatu Event berdasarkan Nomor WhatsApp/HP, Email, atau Kode Tiket (UC-04 Alternative Flow)
     */
    public function findGuestByContact(Event $event, string $contact): ?Guest
    {
        $raw = trim($contact);
        $normalizedPhone = $this->normalizePhone($raw);

        return Guest::where('event_id', $event->id)
            ->where(function ($query) use ($raw, $normalizedPhone) {
                $query->where('email', strtolower($raw))
                      ->orWhere('ticket_code', strtoupper($raw))
                      ->orWhere('phone', $raw);

                if ($normalizedPhone !== '') {
                    $query->orWhere('phone', $normalizedPhone);
                }
            })
            ->first();
    }

    /**
     * Buat URL pengiriman tiket digital ke WhatsApp tamu (FR-04 / UC-04)
     */
    public function buildWhatsAppUrl(Guest $guest, string $ticketUrl): ?string
    {
        if (empty($guest->phone)) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $guest->phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }

        $event = $guest->event;
        $message = "Halo *{$guest->name}*,\n\n"
            . "Registrasi Anda untuk acara *{$event->name}* telah berhasil tercatat.\n"
            . "• *Kode Tiket:* {$guest->ticket_code}\n"
            . "• *Tanggal:* {$event->event_date->format('d M Y')}" . ($event->event_time ? " ({$event->event_time})" : "") . "\n"
            . "• *Lokasi:* {$event->location}\n\n"
            . "Buka dan simpan QR Code Undangan Anda melalui tautan berikut:\n{$ticketUrl}\n\n"
            . "_Tunjukkan QR Code tersebut kepada petugas check-in di lokasi acara._";

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
    }

    /**
     * Normalisasi format nomor HP/WhatsApp (mis. 0812-3456-7890 -> 081234567890)
     */
    public function normalizePhone(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }

        $cleaned = preg_replace('/[^0-9+]/', '', trim($phone));
        if (str_starts_with($cleaned, '+62')) {
            $cleaned = '0' . substr($cleaned, 3);
        } elseif (str_starts_with($cleaned, '62') && strlen($cleaned) > 10) {
            $cleaned = '0' . substr($cleaned, 2);
        }

        return $cleaned;
    }
}
