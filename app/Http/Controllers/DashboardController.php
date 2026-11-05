<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Tampilkan Dashboard Admin Real-time sesuai Mockup SDD Hal. 79 (FR-13 / UC-13)
     */
    public function index(Request $request): View
    {
        $events = Event::withCount(['guests', 'attendances'])->orderByDesc('event_date')->get();
        $selectedEventId = $request->query('event_id') ?? $request->session()->get('active_event_id');

        $activeEvent = $selectedEventId
            ? $events->firstWhere('id', (int) $selectedEventId)
            : $events->first();

        if ($activeEvent) {
            $request->session()->put('active_event_id', $activeEvent->id);
            $request->session()->put('active_event_name', $activeEvent->name);
        }

        $report = $activeEvent
            ? $this->reportService->summary($activeEvent)
            : [
                'total_guests' => 0,
                'checked_in_count' => 0,
                'pending_count' => 0,
                'attendance_rate' => 0,
                'categories' => [],
                'hourly_slots' => ['07:30' => 0, '08:00' => 0, '09:00' => 0, '10:00' => 0, '11:00+' => 0],
                'peak_slot' => '09:00',
                'peak_count' => 0,
                'guests' => collect(),
            ];

        $recentAttendances = Attendance::with(['guest.event', 'scanner:id,name'])
            ->when($activeEvent, function ($query) use ($activeEvent) {
                $query->whereHas('guest', fn ($q) => $q->where('event_id', $activeEvent->id));
            })
            ->latest('checked_in_at')
            ->limit(8)
            ->get();

        $totalPetugas = User::where('role', 'petugas')->where('is_active', true)->count();

        return view('admin.dashboard', compact(
            'events',
            'activeEvent',
            'report',
            'recentAttendances',
            'totalPetugas'
        ));
    }
}
