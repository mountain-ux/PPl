<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    public function __construct(
        protected RegistrationService $registrationService
    ) {}

    /**
     * Halaman Landing Page Tamu per Event (Mockup SDD Hal. 90)
     */
    public function landing(string $slug): View
    {
        $event = Event::where('registration_slug', $slug)
            ->withCount(['guests', 'attendances'])
            ->firstOrFail();

        return view('public.landing', compact('event'));
    }

    /**
     * Halaman Formulir Registrasi / RSVP Publik bagi Tamu (FR-02, UC-02, NFR-03, Mockup SDD Hal. 91)
     */
    public function showRegistrationForm(string $slug): View
    {
        $event = Event::where('registration_slug', $slug)->firstOrFail();

        return view('public.register', [
            'event' => $event,
            'customFields' => $event->form_fields ?? [],
        ]);
    }

    /**
     * Simpan Registrasi Mandiri Tamu & Terbitkan QR Code Unik (UC-02, UC-03, UC-04)
     */
    public function submitRegistration(Request $request, string $slug): RedirectResponse
    {
        $event = Event::where('registration_slug', $slug)->firstOrFail();

        if (!$event->is_registration_open) {
            return back()->withErrors([
                'registration' => 'Mohon maaf, pendaftaran untuk acara ini telah ditutup oleh panitia.',
            ]);
        }

        // Rate Limiting pengiriman formulir registrasi (SDD Bab V Security Hal. 96)
        $throttleKey = 'rsvp:' . $event->id . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'name' => "Terlalu banyak permintaan registrasi dari perangkat Anda. Coba lagi dalam {$seconds} detik.",
            ]);
        }
        RateLimiter::hit($throttleKey, 60);

        $rules = $this->registrationService->buildValidationRules($event);
        $validated = $request->validate($rules, [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor WhatsApp/HP aktif wajib diisi untuk pengiriman tiket QR.',
            'phone.regex' => 'Format nomor WhatsApp/HP tidak valid (gunakan angka 8-20 digit).',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $guest = $this->registrationService->registerGuest($event, $validated, 'mandiri');

        return redirect()->route('public.register.success', [
            'slug' => $event->registration_slug,
            'ticketCode' => $guest->ticket_code,
        ])->with('success', 'Registrasi berhasil! Tiket QR Code Anda telah diterbitkan.');
    }

    /**
     * Halaman Registrasi Berhasil (Mockup SDD Hal. 92)
     */
    public function registrationSuccess(string $slug, string $ticketCode): View
    {
        $event = Event::where('registration_slug', $slug)->firstOrFail();
        $guest = Guest::where('event_id', $event->id)
            ->where('ticket_code', $ticketCode)
            ->firstOrFail();

        $ticketUrl = route('public.ticket.qr', [
            'slug' => $event->registration_slug,
            'ticketCode' => $guest->ticket_code,
        ]);

        $waShareUrl = $this->registrationService->buildWhatsAppUrl($guest, $ticketUrl);

        return view('public.success', compact('event', 'guest', 'ticketUrl', 'waShareUrl'));
    }

    /**
     * Halaman Tiket Digital & QR Code Undangan Tamu (FR-04, UC-04, Mockup SDD Hal. 93)
     */
    public function showTicketQr(string $slug, string $ticketCode): View
    {
        $event = Event::where('registration_slug', $slug)->firstOrFail();
        $guest = Guest::where('event_id', $event->id)
            ->where('ticket_code', $ticketCode)
            ->with('attendance')
            ->firstOrFail();

        $ticketUrl = route('public.ticket.qr', [
            'slug' => $event->registration_slug,
            'ticketCode' => $guest->ticket_code,
        ]);

        $waShareUrl = $this->registrationService->buildWhatsAppUrl($guest, $ticketUrl);

        return view('public.ticket-qr', compact('event', 'guest', 'ticketUrl', 'waShareUrl'));
    }

    /**
     * Halaman Cari / Kirim Ulang QR Code Undangan (UC-04 Alternative Flow, NFR-05, Mockup SDD Hal. 94)
     */
    public function showFindQrForm(Request $request, string $slug): View
    {
        $event = Event::where('registration_slug', $slug)->firstOrFail();
        $contact = trim((string) $request->query('contact', ''));
        $foundGuest = null;
        $waShareUrl = null;
        $searched = false;

        if ($contact !== '') {
            $searched = true;
            $foundGuest = $this->registrationService->findGuestByContact($event, $contact);

            if ($foundGuest) {
                $ticketUrl = route('public.ticket.qr', [
                    'slug' => $event->registration_slug,
                    'ticketCode' => $foundGuest->ticket_code,
                ]);
                $waShareUrl = $this->registrationService->buildWhatsAppUrl($foundGuest, $ticketUrl);
            }
        }

        return view('public.find-qr', compact('event', 'contact', 'foundGuest', 'waShareUrl', 'searched'));
    }
}
