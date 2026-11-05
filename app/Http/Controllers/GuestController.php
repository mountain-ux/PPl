<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestImportService;
use App\Services\QrCodeService;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GuestController extends Controller
{
    public function __construct(
        protected RegistrationService $registrationService,
        protected QrCodeService $qrCodeService,
        protected GuestImportService $guestImportService
    ) {}

    /**
     * Halaman Kelola Data Tamu & Tiket pada suatu Event (FR-05, UC-05, Mockup SDD Hal. 80-81)
     */
    public function index(Request $request, Event $event): View
    {
        Gate::authorize('manage', $event);

        $request->session()->put('active_event_id', $event->id);
        $request->session()->put('active_event_name', $event->name);

        $search = trim((string) $request->query('q', ''));
        $statusFilter = $request->query('status', 'all'); // all, checked_in, pending, qr_missing
        $sourceFilter = $request->query('source', 'all'); // all, mandiri, admin, impor

        $guestsQuery = Guest::where('event_id', $event->id)
            ->with('attendance.scanner:id,name');

        if ($search !== '') {
            $guestsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('ticket_code', 'like', "%{$search}%");
            });
        }

        if ($statusFilter === 'checked_in') {
            $guestsQuery->whereHas('attendance');
        } elseif ($statusFilter === 'pending') {
            $guestsQuery->whereDoesntHave('attendance');
        } elseif ($statusFilter === 'qr_missing') {
            $guestsQuery->whereNull('qr_code');
        }

        if (in_array($sourceFilter, ['mandiri', 'admin', 'impor'], true)) {
            $guestsQuery->where('source', $sourceFilter);
        }

        $guests = $guestsQuery->latest('registered_at')->paginate(15)->withQueryString();

        $totalGuests = Guest::where('event_id', $event->id)->count();
        $checkedInCount = Guest::where('event_id', $event->id)->whereHas('attendance')->count();
        $qrMissingCount = Guest::where('event_id', $event->id)->whereNull('qr_code')->count();

        $stats = [
            'total' => $totalGuests,
            'checked_in' => $checkedInCount,
            'pending' => max(0, $totalGuests - $checkedInCount),
            'qr_missing' => $qrMissingCount,
            'rate' => $totalGuests > 0 ? round(($checkedInCount / $totalGuests) * 100, 1) : 0,
        ];

        return view('guests.index', compact(
            'event',
            'guests',
            'stats',
            'search',
            'statusFilter',
            'sourceFilter'
        ));
    }

    /**
     * Check-in Manual 1-Klik dari Halaman Data Tamu (Sesuai Tombol "Check-in Manual" di Mockup SDD Hal. 80-81)
     */
    public function manualCheckIn(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_unless($guest->event_id === $event->id, 404);

        if ($guest->attendance) {
            return back()->with('success', "Tamu '{$guest->name}' sudah tercatat hadir sebelumnya.");
        }

        Attendance::create([
            'guest_id' => $guest->id,
            'checked_in_by' => $request->user()->id,
            'checked_in_at' => now(),
            'scan_method' => 'online',
            'device_id' => 'ADMIN-MANUAL-PANEL',
            'local_uuid' => (string) Str::uuid(),
            'sync_status' => 'berhasil',
            'synced_at' => now(),
        ]);

        return back()->with('success', "Check-in manual untuk '{$guest->name}' (#{$guest->ticket_code}) berhasil dicatat.");
    }

    /**
     * Tambah Data Tamu Secara Manual oleh Admin (FR-05 / UC-05)
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $rules = $this->registrationService->buildValidationRules($event);
        $rules['phone'] = ['nullable', 'string', 'max:30'];

        $validated = $request->validate($rules, [
            'name.required' => 'Nama tamu wajib diisi.',
        ]);

        $guest = $this->registrationService->registerGuest($event, $validated, 'admin');

        return redirect()->route('events.guests.index', $event)
            ->with('success', "Tamu '{$guest->name}' berhasil ditambahkan dengan kode tiket {$guest->ticket_code}.");
    }

    /**
     * Ubah Data Tamu Manual (FR-05 / UC-05)
     */
    public function update(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_unless($guest->event_id === $event->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('guests', 'email')
                    ->where('event_id', $event->id)
                    ->ignore($guest->id),
            ],
            'custom_data' => ['nullable', 'array'],
        ]);

        $normalizedPhone = $this->registrationService->normalizePhone($validated['phone'] ?? '');

        $guest->update([
            'name' => trim($validated['name']),
            'phone' => $normalizedPhone ?: null,
            'email' => !empty($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'custom_data' => $validated['custom_data'] ?? $guest->custom_data,
        ]);

        return redirect()->route('events.guests.index', $event)
            ->with('success', "Data tamu '{$guest->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Data Tamu dengan Konfirmasi (FR-05 / UC-05 & NFR-06)
     */
    public function destroy(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_unless($guest->event_id === $event->id, 404);

        $guestName = $guest->name;
        $guest->delete();

        return redirect()->route('events.guests.index', $event)
            ->with('success', "Data tamu '{$guestName}' berhasil dihapus.");
    }

    /**
     * Generate Ulang QR Code untuk Tamu yang berstatus "QR belum terbit" (UC-03 Exception Flow)
     */
    public function regenerateQr(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manage', $event);
        abort_unless($guest->event_id === $event->id, 404);

        $payload = $this->qrCodeService->generate($guest);
        $guest->update(['qr_code' => $payload]);

        return redirect()->route('events.guests.index', $event)
            ->with('success', "QR Code untuk '{$guest->name}' ({$guest->ticket_code}) berhasil diterbitkan ulang.");
    }

    /**
     * Halaman Impor Data Tamu Massal (FR-06 / UC-06, Mockup SDD Hal. 82-83)
     */
    public function showImportForm(Request $request, Event $event): View
    {
        Gate::authorize('manage', $event);

        $preview = $request->session()->get("import_preview_{$event->id}");

        return view('guests.import', compact('event', 'preview'));
    }

    /**
     * Unduh Template CSV Impor Tamu sesuai Kolom Field Kustom Event (Mockup SDD Hal. 82)
     */
    public function downloadTemplate(Event $event): StreamedResponse
    {
        Gate::authorize('manage', $event);

        $headers = ['nama', 'no_hp', 'email'];
        foreach ($event->form_fields ?? [] as $field) {
            if (!empty($field['name'])) {
                $headers[] = $field['name'];
            }
        }

        $filename = "Template_Import_TAMUIN_{$event->registration_slug}.csv";

        return response()->streamDownload(function () use ($headers, $event) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            $sample1 = ['Dr. Ir. H. Iskandar, M.T.', '081244558899', 'iskandar@unsulbar.ac.id'];
            $sample2 = ['Nur Aisyah Maharani', '085289881122', 'aisyah.m@student.unsulbar.ac.id'];

            foreach ($event->form_fields ?? [] as $field) {
                $firstOption = $field['options'][0] ?? 'Contoh Isian';
                $sample1[] = $firstOption;
                $sample2[] = $field['options'][1] ?? $firstOption;
            }

            fputcsv($out, $sample1);
            fputcsv($out, $sample2);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Unggah & Validasi Preview Berkas Impor Data Tamu (UC-06 Basic & Alternative Flow)
     */
    public function previewImport(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $request->validate([
            'import_file' => ['required', 'file', 'max:5120'],
        ], [
            'import_file.required' => 'Pilih berkas Excel (.xlsx) atau CSV (.csv) terlebih dahulu.',
            'import_file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        try {
            $file = $request->file('import_file');
            $result = $this->guestImportService->parseAndValidate($event, $file);
            $result['file_name'] = $file->getClientOriginalName();
            $result['file_size_kb'] = round($file->getSize() / 1024, 1);

            $request->session()->put("import_preview_{$event->id}", $result);

            return redirect()->route('events.guests.import.form', $event)
                ->with('success', "Berkas '{$result['file_name']}' berhasil diperiksa: {$result['valid_count']} data valid, {$result['invalid_count']} baris perlu diperbaiki.");
        } catch (Throwable $e) {
            return back()->withErrors([
                'import_file' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Eksekusi Penyimpanan Baris Valid dari Preview Impor & Buat QR Code (UC-06)
     */
    public function confirmImport(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $preview = $request->session()->get("import_preview_{$event->id}");
        if (!$preview || empty($preview['valid_rows'])) {
            return redirect()->route('events.guests.import.form', $event)
                ->withErrors(['import_file' => 'Tidak ada data valid pada sesi preview untuk diimpor.']);
        }

        $importedCount = $this->guestImportService->importValidRows($event, $preview['valid_rows']);
        $request->session()->forget("import_preview_{$event->id}");

        return redirect()->route('events.guests.index', $event)
            ->with('success', "Impor selesai! Sebanyak {$importedCount} data tamu valid berhasil disimpan beserta QR Code unik masing-masing.");
    }

    /**
     * Reset Sesi Preview Impor
     */
    public function resetImportPreview(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);
        $request->session()->forget("import_preview_{$event->id}");

        return redirect()->route('events.guests.import.form', $event);
    }

    /**
     * Ekspor Daftar Tamu Event ke CSV (Mockup Halaman Data Tamu SDD Hal. 80)
     */
    public function exportCsv(Event $event): StreamedResponse
    {
        Gate::authorize('manage', $event);

        $customFields = $event->form_fields ?? [];
        $guests = Guest::where('event_id', $event->id)
            ->with('attendance.scanner:id,name')
            ->orderBy('name')
            ->get();

        $filename = "Data_Tamu_{$event->registration_slug}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($guests, $customFields) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $headers = ['Kode Tiket', 'Nama Lengkap', 'No WhatsApp/HP', 'Email', 'Sumber Data', 'Status Kehadiran', 'Waktu Check-in'];
            foreach ($customFields as $field) {
                $headers[] = $field['label'] ?? $field['name'];
            }
            fputcsv($out, $headers);

            foreach ($guests as $g) {
                $row = [
                    $g->ticket_code,
                    $g->name,
                    $g->phone ?? '-',
                    $g->email ?? '-',
                    strtoupper($g->source),
                    $g->attendance ? 'SUDAH HADIR' : 'BELUM HADIR',
                    $g->attendance ? $g->attendance->checked_in_at->format('Y-m-d H:i:s') : '-',
                ];
                foreach ($customFields as $field) {
                    $key = $field['name'] ?? '';
                    $row[] = $g->custom_data[$key] ?? '-';
                }
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
