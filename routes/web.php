<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - TAMUIN (Full Release: Increment 1 s/d 5)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $latestEvent = \App\Models\Event::where('is_registration_open', true)->orderByDesc('event_date')->first();
    if ($latestEvent) {
        return redirect()->route('public.landing', $latestEvent->registration_slug);
    }
    return redirect()->route('login');
});

// =========================================================================
// MODUL 3: REGISTRASI / RSVP PUBLIK & UNDANGAN QR TAMU (FR-02, FR-03, FR-04)
// =========================================================================
Route::prefix('e/{slug}')->name('public.')->group(function () {
    Route::get('/', [PublicRegistrationController::class, 'landing'])->name('landing');
    Route::get('/register', [PublicRegistrationController::class, 'showRegistrationForm'])->name('register.form');
    Route::post('/register', [PublicRegistrationController::class, 'submitRegistration'])->name('register.submit');
    Route::get('/register/{ticketCode}/success', [PublicRegistrationController::class, 'registrationSuccess'])->name('register.success');
    Route::get('/ticket/{ticketCode}', [PublicRegistrationController::class, 'showTicketQr'])->name('ticket.qr');
    Route::get('/find-qr', [PublicRegistrationController::class, 'showFindQrForm'])->name('find-qr');
});

// =========================================================================
// MODUL 1: AUTENTIKASI LOGIN & LOGOUT (FR-01 / UC-01)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// =========================================================================
// AREA TERAUTENTIKASI (ADMIN & PETUGAS CHECK-IN AKTIF)
// =========================================================================
Route::middleware(['auth', 'active.role:admin,petugas'])->group(function () {

    // Modul 5: Kiosk Check-in & Validasi Offline (FR-07, FR-08, FR-09, FR-10, FR-11, FR-12, FR-15, FR-19)
    Route::prefix('kiosk')->name('kiosk.')->group(function () {
        Route::get('/', [KioskController::class, 'index'])->name('index');
        Route::get('/api/events/{event}/guests', [KioskController::class, 'pullGuests'])->name('api.pull-guests');
        Route::post('/api/events/{event}/sync', [KioskController::class, 'pushSync'])->name('api.push-sync');
    });

    // Modul 2: Manajemen Event, Konfigurasi Formulir Pendaftaran, & Pilih Event Aktif (UC-17, UC-18, UC-19)
    Route::resource('events', EventController::class)->except(['show']);
    Route::get('/events/{event}/form-fields', [EventController::class, 'editFormFields'])
        ->name('events.form-fields.edit');
    Route::put('/events/{event}/form-fields', [EventController::class, 'updateFormFields'])
        ->name('events.form-fields.update');
    Route::post('/events/{event}/select-active', [EventController::class, 'selectActiveEvent'])
        ->name('events.select-active');

    // Modul 4: Pengelolaan Data Tamu Manual & Impor Massal per Event (FR-05, FR-06 / UC-05, UC-06)
    Route::prefix('events/{event}/guests')->name('events.guests.')->group(function () {
        Route::get('/', [GuestController::class, 'index'])->name('index');
        Route::post('/', [GuestController::class, 'store'])->name('store');
        Route::put('/{guest}', [GuestController::class, 'update'])->name('update');
        Route::delete('/{guest}', [GuestController::class, 'destroy'])->name('destroy');
        Route::post('/{guest}/regenerate-qr', [GuestController::class, 'regenerateQr'])->name('regenerate-qr');
        Route::post('/{guest}/manual-checkin', [GuestController::class, 'manualCheckIn'])->name('manual-checkin');

        // Ekspor & Impor Data Tamu Massal
        Route::get('/export-csv', [GuestController::class, 'exportCsv'])->name('export-csv');
        Route::get('/import', [GuestController::class, 'showImportForm'])->name('import.form');
        Route::get('/import/template', [GuestController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import/preview', [GuestController::class, 'previewImport'])->name('import.preview');
        Route::post('/import/confirm', [GuestController::class, 'confirmImport'])->name('import.confirm');
        Route::post('/import/reset', [GuestController::class, 'resetImportPreview'])->name('import.reset');
    });

    // Area Khusus Admin: Dashboard Real-time, Laporan Kehadiran (PDF/Excel), & Kelola Petugas (UC-13, UC-14, UC-16)
    Route::middleware('active.role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Modul 6: Laporan Kehadiran & Ekspor PDF / Excel (FR-14 / UC-14)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{event}/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
        Route::get('/reports/{event}/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

        // Kelola Akun Petugas Check-in (FR-16 / UC-16)
        Route::get('/petugas', [PetugasController::class, 'index'])->name('petugas.index');
        Route::post('/petugas', [PetugasController::class, 'store'])->name('petugas.store');
        Route::put('/petugas/{petugas}', [PetugasController::class, 'update'])->name('petugas.update');
        Route::patch('/petugas/{petugas}/toggle-status', [PetugasController::class, 'toggleStatus'])
            ->name('petugas.toggle-status');
    });
});
