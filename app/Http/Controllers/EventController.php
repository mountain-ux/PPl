<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Daftar Event sesuai peran aktor (FR-17 / UC-17):
     * Admin melihat seluruh Event, Petugas hanya melihat Event yang dibuatnya sendiri.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeEventId = $request->session()->get('active_event_id');

        $events = Event::accessibleBy($user)
            ->with('creator:id,name,username,role')
            ->withCount(['guests', 'attendances'])
            ->orderByDesc('event_date')
            ->paginate(10);

        return view('events.index', compact('events', 'activeEventId'));
    }

    /**
     * Tampilkan form tambah Event baru
     */
    public function create(): View
    {
        return view('events.form', [
            'event' => new Event([
                'event_date' => now()->format('Y-m-d'),
                'is_registration_open' => true,
            ]),
            'isEdit' => false,
        ]);
    }

    /**
     * Simpan Event baru dan kaitkan dengan aktor pembuat (UC-17 Basic Flow)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_registration_open' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama Event wajib diisi.',
            'event_date.required' => 'Tanggal pelaksanaan Event wajib diisi.',
            'location.required' => 'Lokasi pelaksanaan Event wajib diisi.',
        ]);

        $event = Event::create([
            'name' => $validated['name'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'] ?? null,
            'location' => $validated['location'],
            'description' => $validated['description'] ?? null,
            'registration_slug' => Event::generateUniqueSlug($validated['name']),
            'is_registration_open' => $request->boolean('is_registration_open', true),
            'created_by' => $request->user()->id,
            'form_fields' => [],
        ]);

        return redirect()->route('events.form-fields.edit', $event)
            ->with('success', "Event '{$event->name}' berhasil dibuat. Silakan atur konfigurasi formulir pendaftaran tamu.");
    }

    /**
     * Tampilkan halaman ubah data Event (dengan pengecekan EventPolicy)
     */
    public function edit(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('events.form', [
            'event' => $event,
            'isEdit' => true,
        ]);
    }

    /**
     * Simpan perubahan data Event (UC-17)
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_registration_open' => ['nullable', 'boolean'],
        ]);

        $event->update([
            'name' => $validated['name'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'] ?? null,
            'location' => $validated['location'],
            'description' => $validated['description'] ?? null,
            'is_registration_open' => $request->boolean('is_registration_open'),
        ]);

        return redirect()->route('events.index')
            ->with('success', "Data Event '{$event->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Event (beserta konfirmasi sesuai NFR-06)
     */
    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $eventName = $event->name;
        $event->delete();

        return redirect()->route('events.index')
            ->with('success', "Event '{$eventName}' berhasil dihapus.");
    }

    /**
     * Halaman Konfigurasi Formulir Pendaftaran per Event (FR-18 / UC-18)
     */
    public function editFormFields(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('events.form-builder', [
            'event' => $event,
            'fields' => $event->form_fields ?? [],
            'maxFields' => Event::MAX_CUSTOM_FIELDS,
        ]);
    }

    /**
     * Simpan Konfigurasi Field Kustom Formulir Pendaftaran (FR-18 / UC-18 & NFR-23: Maks 10 Field)
     */
    public function updateFormFields(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $validated = $request->validate([
            'fields' => ['nullable', 'array', 'max:' . Event::MAX_CUSTOM_FIELDS],
            'fields.*.label' => ['required', 'string', 'max:100'],
            'fields.*.type' => ['required', 'in:text,number,select,textarea'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.options_raw' => ['nullable', 'string', 'max:500'],
        ], [
            'fields.max' => 'Jumlah field kustom dibatasi maksimal ' . Event::MAX_CUSTOM_FIELDS . ' field agar formulir tetap singkat bagi tamu.',
            'fields.*.label.required' => 'Label setiap field kustom wajib diisi.',
        ]);

        $normalizedFields = [];
        $usedKeys = [];

        foreach ($validated['fields'] ?? [] as $index => $item) {
            $baseKey = Str::slug($item['label'], '_') ?: ('field_' . ($index + 1));
            $key = $baseKey;
            $suffix = 1;

            while (in_array($key, $usedKeys, true)) {
                $key = $baseKey . '_' . (++$suffix);
            }
            $usedKeys[] = $key;

            $options = [];
            if ($item['type'] === 'select' && !empty($item['options_raw'])) {
                $raw = $item['options_raw'];
                // Gunakan pemisah '|' atau ';' jika ada, atau ',' jika tidak ada '|' / ';'
                $delimiter = str_contains($raw, '|') ? '|' : (str_contains($raw, ';') ? ';' : ',');
                $options = array_values(array_filter(array_map('trim', explode($delimiter, $raw))));
            }

            $normalizedFields[] = [
                'name' => $key,
                'label' => trim($item['label']),
                'type' => $item['type'],
                'is_required' => !empty($item['is_required']),
                'options' => $options,
            ];
        }

        $event->update([
            'form_fields' => $normalizedFields,
        ]);

        return redirect()->route('events.form-fields.edit', $event)
            ->with('success', "Konfigurasi formulir pendaftaran untuk '{$event->name}' berhasil disimpan (" . count($normalizedFields) . " field kustom).");
    }

    /**
     * Pilih Event Aktif untuk sesi Kiosk dan arahkan ke halaman Kiosk (FR-19 / UC-19)
     */
    public function selectActiveEvent(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('view', $event);

        $request->session()->put('active_event_id', $event->id);
        $request->session()->put('active_event_name', $event->name);

        return redirect()->route('kiosk.index', ['event_id' => $event->id])
            ->with('success', "Event aktif di Kiosk ditetapkan ke: '{$event->name}'. Silakan unduh data tamu untuk kesiapan offline.");
    }
}
