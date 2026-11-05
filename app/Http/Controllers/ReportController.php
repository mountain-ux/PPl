<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Halaman Laporan Kehadiran & Rekapitulasi Presensi (FR-14 / UC-14, Mockup SDD Hal. 83-84)
     */
    public function index(Request $request): View
    {
        $events = Event::withCount(['guests', 'attendances'])->orderByDesc('event_date')->get();
        $selectedEventId = $request->query('event_id') ?? $request->session()->get('active_event_id');

        $activeEvent = $selectedEventId
            ? $events->firstWhere('id', (int) $selectedEventId)
            : $events->first();

        $statusFilter = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $report = $activeEvent
            ? $this->reportService->summary($activeEvent, $statusFilter, $search)
            : [
                'total_guests' => 0,
                'checked_in_count' => 0,
                'pending_count' => 0,
                'attendance_rate' => 0,
                'categories' => [],
                'hourly_slots' => [],
                'peak_slot' => '-',
                'peak_count' => 0,
                'guests' => collect(),
            ];

        return view('admin.reports.index', compact(
            'events',
            'activeEvent',
            'report',
            'statusFilter',
            'search'
        ));
    }

    /**
     * Unduh Berkas Laporan Kehadiran dalam Format Microsoft Excel (.xlsx) (FR-14 / UC-14)
     */
    public function exportExcel(Request $request, Event $event): StreamedResponse
    {
        $statusFilter = $request->query('status', 'all');
        return $this->reportService->exportExcel($event, $statusFilter);
    }

    /**
     * Tampilkan / Cetak Laporan Akhir Kehadiran dalam Format PDF Siap Cetak (FR-14 / UC-14 & NFR-17)
     */
    public function exportPdf(Request $request, Event $event): View
    {
        $statusFilter = $request->query('status', 'all');
        $report = $this->reportService->summary($event, $statusFilter);

        return view('admin.reports.print-pdf', compact('event', 'report', 'statusFilter'));
    }
}
