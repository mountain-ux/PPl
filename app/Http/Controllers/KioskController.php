<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KioskController extends Controller
{
    public function __construct(
        protected SyncService $syncService
    ) {}

    /**
     * Halaman Utama Kiosk Check-in Offline-First (UC-07, UC-08, UC-09, UC-10, UC-11, UC-15, UC-19)
     * Menampilkan pemilihan Event aktif, pengunduhan data ke IndexedDB, Scanner QR Kamera,
     * Input Manual, serta panel Status & Sinkronisasi.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Daftar Event yang dapat diakses oleh Petugas/Admin (FR-17 & FR-19)
        $accessibleEvents = Event::accessibleBy($user)
            ->withCount(['guests', 'attendances'])
            ->orderByDesc('event_date')
            ->get();

        $requestedEventId = $request->query('event_id') ?? $request->session()->get('active_event_id');

        $activeEvent = null;
        if ($requestedEventId) {
            $activeEvent = $accessibleEvents->firstWhere('id', (int) $requestedEventId);
        }
        if (!$activeEvent && $accessibleEvents->isNotEmpty()) {
            $activeEvent = $accessibleEvents->first();
        }

        if ($activeEvent) {
            $request->session()->put('active_event_id', $activeEvent->id);
            $request->session()->put('active_event_name', $activeEvent->name);
        }

        return view('kiosk.index', compact('accessibleEvents', 'activeEvent'));
    }

    /**
     * Endpoint API: Unduh seluruh data tamu Event aktif ke penyimpanan lokal IndexedDB Kiosk (FR-07 / UC-07 & NFR-08)
     */
    public function pullGuests(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $guests = Guest::where('event_id', $event->id)
            ->with('attendance:id,guest_id,checked_in_at,scan_method,device_id,local_uuid')
            ->orderBy('name')
            ->get()
            ->map(function (Guest $guest) {
                return [
                    'id' => $guest->id,
                    'event_id' => $guest->event_id,
                    'name' => $guest->name,
                    'phone' => $guest->phone ?? '',
                    'email' => $guest->email ?? '',
                    'ticket_code' => $guest->ticket_code,
                    'qr_code' => $guest->qr_code ?? '',
                    'source' => $guest->source,
                    'custom_data' => $guest->custom_data ?? [],
                    'is_checked_in' => $guest->attendance !== null,
                    'checked_in_at' => $guest->attendance ? $guest->attendance->checked_in_at->format('Y-m-d H:i:s') : null,
                    'local_uuid' => $guest->attendance ? $guest->attendance->local_uuid : null,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'ok',
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'event_date' => $event->event_date->format('d F Y'),
                'event_time' => $event->event_time,
                'location' => $event->location,
            ],
            'total_guests' => $guests->count(),
            'downloaded_at' => now()->toIso8601String(),
            'guests' => $guests,
        ]);
    }

    /**
     * Endpoint API: Sinkronisasi data check-in lokal dari IndexedDB Kiosk ke Server MySQL (FR-12 / UC-12)
     */
    public function pushSync(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $validated = $request->validate([
            'attendances' => ['required', 'array', 'max:5000'],
            'attendances.*.local_uuid' => ['required', 'string', 'max:64'],
            'attendances.*.guest_id' => ['required', 'integer'],
            'attendances.*.qr_code' => ['nullable', 'string', 'max:255'],
            'attendances.*.checked_in_at' => ['required', 'string'],
            'attendances.*.scan_method' => ['nullable', 'string'],
            'attendances.*.device_id' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->syncService->receive(
            $event,
            $validated['attendances'],
            $request->user()
        );

        return response()->json([
            'status' => 'ok',
            'message' => 'Sinkronisasi data kehadiran berhasil diproses.',
            'result' => $result,
        ]);
    }
}
