@extends('layouts.app')

@section('title', 'Kiosk Check-in — TAMUIN')

@section('content')
@if(!$activeEvent)
    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 p-8 text-center">
        <h1 class="text-base font-bold text-slate-900">Belum Ada Acara Aktif</h1>
        <p class="text-xs text-slate-500 mt-1">Silakan buat acara terlebih dahulu sebelum memulai sesi Kiosk Check-in.</p>
        <a href="{{ route('events.create') }}" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-blue-700 text-white text-xs font-semibold">
            Buat Acara Baru
        </a>
    </div>
@else
    <div class="max-w-xl md:max-w-2xl mx-auto">
        <!-- Header Kiosk: Pilihan Acara & Status Koneksi -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-4">
            <div class="flex items-center justify-between gap-2 mb-3">
                <form method="GET" action="{{ route('kiosk.index') }}" class="flex-1 min-w-0">
                    <select name="event_id" onchange="this.form.submit()"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-900 truncate outline-none">
                        @foreach($accessibleEvents as $ev)
                            <option value="{{ $ev->id }}" {{ $ev->id === $activeEvent->id ? 'selected' : '' }}>
                                {{ $ev->name }} ({{ $ev->event_date->format('d M Y') }})
                            </option>
                        @endforeach
                    </select>
                </form>

                <div id="connectionBadge" class="px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1.5 shrink-0">
                    <span id="connectionDot" class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span id="connectionText">Online</span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-1.5 bg-slate-100 p-1 rounded-xl text-xs">
                <button type="button" onclick="switchKioskTab('scanner')" id="tabBtn_scanner"
                        class="py-2 rounded-lg font-semibold bg-slate-900 text-white transition">
                    Pemindai QR
                </button>
                <button type="button" onclick="switchKioskTab('guests')" id="tabBtn_guests"
                        class="py-2 rounded-lg font-semibold text-slate-600 hover:text-slate-900 transition">
                    Data Lokal (<span id="badgeLocalGuests">0</span>)
                </button>
                <button type="button" onclick="switchKioskTab('sync')" id="tabBtn_sync"
                        class="py-2 rounded-lg font-semibold text-slate-600 hover:text-slate-900 transition">
                    Sinkronisasi (<span id="badgeUnsynced">0</span>)
                </button>
            </div>
        </div>

        <div id="emptyLocalAlert" class="hidden mb-4 rounded-xl bg-amber-50 border border-amber-200 p-3.5 flex items-center justify-between gap-3 text-xs text-amber-900">
            <div>
                <div class="font-bold">Data Tamu Lokal Belum Diunduh</div>
                <div class="text-[11px]">Unduh daftar tamu agar validasi check-in dapat berjalan tanpa koneksi internet.</div>
            </div>
            <button type="button" onclick="handlePullGuests()" id="btnPullGuests"
                    class="px-3.5 py-2 rounded-lg bg-amber-600 text-white font-semibold shrink-0">
                Unduh Data
            </button>
        </div>

        <!-- TAB 1: PEMINDAI QR & VERIFIKASI -->
        <div id="kioskTab_scanner">
            <div id="scannerViewBox" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-900 text-white p-5">
                    <div class="flex items-center justify-between text-xs text-slate-300 mb-3">
                        <span class="font-medium">Kamera Pemindai Kiosk</span>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnStartCamera" onclick="startQrScanner()"
                                    class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-semibold text-white">
                                Aktifkan Kamera
                            </button>
                            <button type="button" id="btnStopCamera" onclick="stopQrScanner()"
                                    class="hidden px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-xs font-semibold text-white">
                                Matikan Kamera
                            </button>
                        </div>
                    </div>

                    <div class="relative mx-auto max-w-[280px] sm:max-w-[340px] min-h-[240px] sm:min-h-[280px] rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center overflow-hidden">
                        <div id="qrReaderBox" class="w-full"></div>
                        <div id="cameraPlaceholder" class="p-6 text-center space-y-2">
                            <div class="w-36 h-36 mx-auto relative flex items-center justify-center">
                                <div class="absolute top-0 left-0 w-5 h-5 border-t-2 border-l-2 border-blue-500"></div>
                                <div class="absolute top-0 right-0 w-5 h-5 border-t-2 border-r-2 border-blue-500"></div>
                                <div class="absolute bottom-0 left-0 w-5 h-5 border-b-2 border-l-2 border-blue-500"></div>
                                <div class="absolute bottom-0 right-0 w-5 h-5 border-b-2 border-r-2 border-blue-500"></div>
                                <div class="w-full h-px bg-blue-500/80"></div>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-xs text-slate-300 mt-3">
                        Arahkan kode QR pada tiket undangan tamu ke dalam area bidik kamera.
                    </p>

                    <div id="cameraErrorAlert" class="hidden mt-3 rounded-xl bg-rose-950 border border-rose-700 p-3 text-xs text-rose-200 text-center"></div>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Input Manual Kode Tiket (Apabila QR Tidak Terbaca Kamera)
                        </label>
                        <form onsubmit="handleManualCodeSubmit(event)" class="flex gap-2">
                            <input type="text" id="manualCodeInput"
                                   placeholder="Contoh: TMN-2026-88421"
                                   class="flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm font-mono uppercase focus:border-blue-600 outline-none">
                            <button type="submit" class="px-4 py-2.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold shrink-0">
                                Verifikasi
                            </button>
                        </form>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-[10px] font-semibold uppercase text-slate-500">Total Hadir Lokal</div>
                            <div class="text-lg font-bold text-slate-900 mt-0.5" id="statCheckedIn">0</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="text-[10px] font-semibold uppercase text-slate-500">Waktu Validasi</div>
                            <div class="text-lg font-bold text-slate-900 mt-0.5" id="statLatency">-</div>
                        </div>
                    </div>

                    <div id="lastScannedBox" class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                        <div class="flex items-center justify-between text-[10px] font-semibold uppercase text-slate-400 mb-1">
                            <span>Pemindaian Terakhir</span>
                            <span id="lastScannedAgo">Siap</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-semibold text-slate-800" id="lastScannedName">Belum ada pemindaian pada sesi ini</div>
                            <span id="lastScannedBadge" class="hidden px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-semibold">Hadir</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARTU HASIL 1: CHECK-IN BERHASIL -->
            <div id="resultCard_valid" class="hidden bg-white rounded-2xl border-2 border-emerald-500 p-6">
                <div class="text-center pb-4 border-b border-slate-100">
                    <span id="validBadgeStatus" class="inline-block px-3 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[11px] font-semibold border border-emerald-200">
                        Kehadiran Tercatat
                    </span>
                    <h2 id="validHeadline" class="text-xl font-bold text-slate-900 mt-2">Check-in Berhasil</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Waktu Check-in: <span id="validCheckinTime" class="font-semibold text-slate-700">-</span></p>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2.5 text-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-[10px] font-semibold uppercase text-slate-400">Nama Tamu</div>
                            <div id="validGuestName" class="text-base font-bold text-slate-900 mt-0.5">-</div>
                            <div id="validGuestContact" class="text-slate-600 mt-0.5">-</div>
                        </div>
                        <span id="validCategoryBadge" class="px-2.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-semibold">
                            Tamu
                        </span>
                    </div>

                    <div id="validCustomData" class="flex flex-wrap gap-1.5"></div>

                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500">
                        <span>Kode Tiket: <strong id="validTicketCode" class="font-mono text-slate-800">-</strong></span>
                        <span>Durasi Validasi: <strong id="validLatency" class="text-slate-700">-</strong></span>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="button" onclick="resetResultPanel()"
                            class="w-full py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs sm:text-sm font-semibold transition">
                        Pindai Tamu Berikutnya
                    </button>
                </div>
            </div>

            <!-- KARTU HASIL 2: DUPLIKASI SCAN -->
            <div id="resultCard_duplicate" class="hidden bg-white rounded-2xl border-2 border-amber-500 p-6">
                <div class="text-center pb-4 border-b border-slate-100">
                    <span class="inline-block px-3 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 text-[11px] font-semibold">
                        Peringatan Duplikasi
                    </span>
                    <h2 class="text-lg font-bold text-slate-900 mt-2">Tamu Sudah Melakukan Check-in</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tercatat hadir sebelumnya pada: <strong id="dupCheckinTime" class="text-slate-700">-</strong>
                    </p>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-1 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-semibold uppercase text-slate-400">Identitas Pemilik Tiket</span>
                        <span id="dupTicketCode" class="font-mono font-bold text-slate-700">-</span>
                    </div>
                    <div id="dupGuestName" class="text-sm font-bold text-slate-900">-</div>
                    <div id="dupGuestContact" class="text-slate-500">-</div>
                </div>

                <div class="mt-5 space-y-2">
                    <button type="button" onclick="resetResultPanel()"
                            class="w-full py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs sm:text-sm font-semibold">
                        Kembali ke Pemindai (Tolak Duplikasi)
                    </button>
                    <button type="button" onclick="overrideDuplicateCheckin()"
                            class="w-full py-2.5 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 text-xs font-semibold hover:bg-amber-100">
                        Tetap Izinkan Masuk (Kebijakan Acara)
                    </button>
                </div>
            </div>

            <!-- KARTU HASIL 3: QR TIDAK DITEMUKAN -->
            <div id="resultCard_invalid" class="hidden bg-white rounded-2xl border-2 border-rose-500 p-6">
                <div class="text-center pb-4 border-b border-slate-100">
                    <span class="inline-block px-3 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-semibold">
                        Validasi Gagal
                    </span>
                    <h2 class="text-lg font-bold text-slate-900 mt-2">Tiket Tidak Terdaftar</h2>
                    <p id="invalidReasonText" class="text-xs text-slate-500 mt-0.5">
                        Kode QR tidak ditemukan pada data tamu lokal untuk acara ini.
                    </p>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-3.5 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Kode Terbaca:</span>
                    <span id="invalidScannedCode" class="font-mono font-bold text-rose-600">-</span>
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <button type="button" onclick="switchKioskTab('guests')"
                            class="py-2.5 px-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold">
                        Cari Nama Tamu Manual
                    </button>
                    <button type="button" onclick="resetResultPanel()"
                            class="py-2.5 px-4 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                        Pindai Ulang
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 2: PENCARIAN DATA LOKAL -->
        <div id="kioskTab_guests" class="hidden bg-white rounded-2xl border border-slate-200 p-5">
            <h2 class="text-base font-bold text-slate-900">Daftar Tamu Tersimpan di Perangkat</h2>
            <p class="text-xs text-slate-500 mt-0.5 mb-3">Data ini tersimpan di IndexedDB dan dapat diakses secara offline.</p>

            <input type="text" id="localSearchInput" oninput="renderLocalGuestList()"
                   placeholder="Cari nama tamu, nomor telepon, atau kode tiket..."
                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs sm:text-sm focus:border-blue-600 outline-none mb-4">

            <div id="localGuestsCardList" class="space-y-2 max-h-[60vh] overflow-y-auto pr-1"></div>
        </div>

        <!-- TAB 3: STATUS & SINKRONISASI -->
        <div id="kioskTab_sync" class="hidden bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Status Koneksi & Sinkronisasi</h2>
                    <p class="text-xs text-slate-500">Pengelolaan data lokal dan pengiriman ke server pusat</p>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5">
                <div class="text-[11px] font-semibold text-slate-600 mb-2">Mode Koneksi Perangkat (Pengujian Offline/Online)</div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" id="btnSimOnline" onclick="setOfflineSimulation(false)"
                            class="py-2 rounded-lg text-xs font-semibold bg-blue-700 text-white">
                        Mode Online
                    </button>
                    <button type="button" id="btnSimOffline" onclick="setOfflineSimulation(true)"
                            class="py-2 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700">
                        Simulasi Offline
                    </button>
                </div>
            </div>

            <div id="cloudStatusBanner" class="rounded-xl bg-emerald-50 border border-emerald-200 p-3.5">
                <div id="cloudBannerTitle" class="text-xs font-bold text-emerald-900">Terhubung ke Server</div>
                <div id="cloudBannerSub" class="text-[11px] text-emerald-700 mt-0.5">Sinkronisasi otomatis aktif saat terdapat data baru.</div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="text-[10px] font-semibold uppercase text-slate-500">Tamu Lokal</div>
                    <div class="text-xl font-bold text-slate-900 mt-1" id="syncPanelTotalGuests">0</div>
                </div>

                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="text-[10px] font-semibold uppercase text-slate-500">Hadir Lokal</div>
                    <div class="text-xl font-bold text-emerald-600 mt-1" id="syncPanelCheckedIn">0</div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3">
                    <div class="text-[10px] font-semibold uppercase text-amber-800">Belum Sinkron</div>
                    <div class="text-xl font-bold text-amber-600 mt-1" id="syncPanelUnsynced">0</div>
                </div>
            </div>

            <div id="syncAlertBox" class="hidden rounded-xl p-3 text-xs font-medium"></div>

            <div class="space-y-2.5">
                <button type="button" onclick="handleManualSync()" id="btnPushSync"
                        class="w-full py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs sm:text-sm font-semibold transition">
                    Sinkronkan Data Sekarang
                </button>

                <button type="button" onclick="handlePullGuests()"
                        class="w-full py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50">
                    Perbarui Daftar Tamu dari Server
                </button>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-between text-xs text-slate-500">
                <span>Sinkronisasi Terakhir:</span>
                <strong class="text-slate-700" id="syncPanelLastSync">Belum ada</strong>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="/js/kiosk-app.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }

        const ACTIVE_EVENT_ID = {{ $activeEvent->id }};
        const PULL_URL = @json(route('kiosk.api.pull-guests', $activeEvent));
        const PUSH_URL = @json(route('kiosk.api.push-sync', $activeEvent));
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const localStore = new window.TamuinKiosk.LocalStore();
        const checkInService = new window.TamuinKiosk.CheckInService(localStore, ACTIVE_EVENT_ID);
        let pendingGuestForConfirm = null;
        let html5QrScanner = null;
        let isScannerRunning = false;

        const syncClient = new window.TamuinKiosk.SyncClient(localStore, {
            eventId: ACTIVE_EVENT_ID,
            pullUrl: PULL_URL,
            pushUrl: PUSH_URL,
            csrfToken: CSRF_TOKEN,
            onStatusChange: (state) => updateKioskUiState(state),
        });

        document.addEventListener('DOMContentLoaded', async () => {
            await localStore.open();
            syncClient.watchConnection();
            await renderLocalGuestList();

            const existing = await localStore.getGuestsByEvent(ACTIVE_EVENT_ID);
            if (existing.length === 0 && syncClient.isOnline) {
                await handlePullGuests(true);
            }
        });

        function updateKioskUiState(state) {
            document.getElementById('badgeLocalGuests').textContent = state.totalLocalGuests;
            document.getElementById('badgeUnsynced').textContent = state.unsyncedCount;
            document.getElementById('statCheckedIn').textContent = state.checkedInCount;

            document.getElementById('syncPanelTotalGuests').textContent = state.totalLocalGuests;
            document.getElementById('syncPanelCheckedIn').textContent = state.checkedInCount;
            document.getElementById('syncPanelUnsynced').textContent = state.unsyncedCount;

            const lastSync = localStorage.getItem(`tamuin_last_sync_${ACTIVE_EVENT_ID}`);
            if (lastSync) {
                document.getElementById('syncPanelLastSync').textContent = `${lastSync} WITA`;
            }

            document.getElementById('emptyLocalAlert').classList.toggle('hidden', state.totalLocalGuests > 0);

            const badge = document.getElementById('connectionBadge');
            const dot = document.getElementById('connectionDot');
            const text = document.getElementById('connectionText');
            const banner = document.getElementById('cloudStatusBanner');
            const bTitle = document.getElementById('cloudBannerTitle');
            const bSub = document.getElementById('cloudBannerSub');

            if (state.isOnline) {
                badge.className = 'px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1.5 shrink-0';
                dot.className = 'w-2 h-2 rounded-full bg-emerald-500';
                text.textContent = state.unsyncedCount > 0 ? `Online (${state.unsyncedCount})` : 'Online';

                banner.className = 'rounded-xl bg-emerald-50 border border-emerald-200 p-3.5';
                bTitle.className = 'text-xs font-bold text-emerald-900';
                bTitle.textContent = 'Terhubung ke Server';
                bSub.className = 'text-[11px] text-emerald-700 mt-0.5';
                bSub.textContent = 'Sinkronisasi otomatis siap dijalankan.';
            } else {
                badge.className = 'px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1.5 shrink-0';
                dot.className = 'w-2 h-2 rounded-full bg-amber-600';
                text.textContent = `Offline (${state.unsyncedCount})`;

                banner.className = 'rounded-xl bg-amber-50 border border-amber-300 p-3.5';
                bTitle.className = 'text-xs font-bold text-amber-900';
                bTitle.textContent = 'Mode Offline Aktif';
                bSub.className = 'text-[11px] text-amber-800 mt-0.5';
                bSub.textContent = `${state.unsyncedCount} data kehadiran tersimpan secara lokal di perangkat.`;
            }
        }

        async function handlePullGuests(silent = false) {
            try {
                const res = await syncClient.pullGuests();
                await renderLocalGuestList();
                if (!silent) {
                    showSyncAlert(`Berhasil mengunduh ${res.total_guests} data tamu ke penyimpanan lokal.`, 'success');
                }
            } catch (err) {
                if (!silent) alert(err.message);
            }
        }

        async function handleManualSync() {
            try {
                const res = await syncClient.pushUnsynced();
                if (res.skipped) {
                    showSyncAlert('Perangkat sedang berada dalam Mode Offline. Aktifkan Mode Online untuk menyinkronkan data.', 'warning');
                    return;
                }
                showSyncAlert(`Sinkronisasi berhasil. ${res.synced} data kehadiran telah disimpan ke server.`, 'success');
            } catch (err) {
                showSyncAlert(`Sinkronisasi tertunda: ${err.message}`, 'error');
            }
        }

        function showSyncAlert(message, type = 'success') {
            const box = document.getElementById('syncAlertBox');
            box.classList.remove('hidden');
            box.className = type === 'success'
                ? 'rounded-xl p-3 text-xs font-medium bg-emerald-50 border border-emerald-200 text-emerald-800'
                : 'rounded-xl p-3 text-xs font-medium bg-amber-50 border border-amber-200 text-amber-900';
            box.textContent = message;
        }

        function setOfflineSimulation(isOffline) {
            syncClient.setSimulatedOffline(isOffline);
            document.getElementById('btnSimOnline').className = !isOffline
                ? 'py-2 rounded-lg text-xs font-semibold bg-blue-700 text-white'
                : 'py-2 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700';
            document.getElementById('btnSimOffline').className = isOffline
                ? 'py-2 rounded-lg text-xs font-semibold bg-amber-600 text-white'
                : 'py-2 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700';
        }

        async function processScannedCode(code) {
            if (isScannerRunning) {
                await stopQrScanner();
            }
            const result = await checkInService.validate(code);
            document.getElementById('statLatency').textContent = `${result.latency_ms} ms`;

            document.getElementById('scannerViewBox').classList.add('hidden');
            ['resultCard_valid', 'resultCard_duplicate', 'resultCard_invalid'].forEach((id) => {
                document.getElementById(id).classList.add('hidden');
            });

            if (result.status === 'valid') {
                const guest = result.guest;
                const saved = await checkInService.confirm(guest, syncClient.isOnline);

                document.getElementById('validTicketCode').textContent = guest.ticket_code;
                document.getElementById('validGuestName').textContent = guest.name;
                document.getElementById('validGuestContact').textContent = `${guest.phone || '-'} | ${guest.email || '-'}`;
                document.getElementById('validCheckinTime').textContent = `${saved.guest.checked_in_at} WITA`;
                document.getElementById('validLatency').textContent = `${result.latency_ms} ms`;

                const cat = (guest.custom_data && guest.custom_data.kategori_tamu) ? guest.custom_data.kategori_tamu : 'Peserta';
                document.getElementById('validCategoryBadge').textContent = cat;

                document.getElementById('lastScannedName').textContent = `${guest.name} (${guest.ticket_code})`;
                document.getElementById('lastScannedAgo').textContent = 'Baru Saja';
                document.getElementById('lastScannedBadge').classList.remove('hidden');

                document.getElementById('resultCard_valid').classList.remove('hidden');
                playBeep(880, 120);

                await syncClient.notifyStatus();
                await renderLocalGuestList();
                if (syncClient.isOnline) {
                    syncClient.pushUnsynced().catch(() => {});
                }
            } else if (result.status === 'duplicate') {
                pendingGuestForConfirm = result.guest;
                document.getElementById('dupTicketCode').textContent = result.guest.ticket_code;
                document.getElementById('dupGuestName').textContent = result.guest.name;
                document.getElementById('dupGuestContact').textContent = `${result.guest.phone || '-'} | ${result.guest.email || '-'}`;
                document.getElementById('dupCheckinTime').textContent = `${result.guest.checked_in_at || '-'} WITA`;
                document.getElementById('resultCard_duplicate').classList.remove('hidden');
                playBeep(300, 280);
            } else {
                document.getElementById('invalidScannedCode').textContent = result.code || '-';
                document.getElementById('invalidReasonText').textContent = result.message;
                document.getElementById('resultCard_invalid').classList.remove('hidden');
                playBeep(220, 350);
            }
        }

        function overrideDuplicateCheckin() {
            if (!pendingGuestForConfirm) return;
            document.getElementById('resultCard_duplicate').classList.add('hidden');
            document.getElementById('validTicketCode').textContent = pendingGuestForConfirm.ticket_code;
            document.getElementById('validGuestName').textContent = pendingGuestForConfirm.name;
            document.getElementById('validGuestContact').textContent = `${pendingGuestForConfirm.phone || '-'}`;
            document.getElementById('validCheckinTime').textContent = pendingGuestForConfirm.checked_in_at;
            document.getElementById('resultCard_valid').classList.remove('hidden');
            pendingGuestForConfirm = null;
        }

        function resetResultPanel() {
            pendingGuestForConfirm = null;
            ['resultCard_valid', 'resultCard_duplicate', 'resultCard_invalid'].forEach((id) => {
                document.getElementById(id).classList.add('hidden');
            });
            document.getElementById('scannerViewBox').classList.remove('hidden');
        }

        function handleManualCodeSubmit(e) {
            e.preventDefault();
            const input = document.getElementById('manualCodeInput');
            const val = input.value.trim();
            if (!val) return;
            processScannedCode(val);
            input.value = '';
        }

        async function startQrScanner() {
            const errBox = document.getElementById('cameraErrorAlert');
            errBox.classList.add('hidden');

            try {
                if (!html5QrScanner) {
                    html5QrScanner = new Html5Qrcode('qrReaderBox');
                }
                document.getElementById('cameraPlaceholder').classList.add('hidden');
                document.getElementById('btnStartCamera').classList.add('hidden');
                document.getElementById('btnStopCamera').classList.remove('hidden');

                await html5QrScanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 200, height: 200 } },
                    (decodedText) => processScannedCode(decodedText),
                    () => {}
                );
                isScannerRunning = true;
            } catch (err) {
                document.getElementById('cameraPlaceholder').classList.remove('hidden');
                document.getElementById('btnStartCamera').classList.remove('hidden');
                document.getElementById('btnStopCamera').classList.add('hidden');
                errBox.textContent = 'Kamera tidak dapat diakses. Gunakan kolom Input Manual Kode Tiket di bawah.';
                errBox.classList.remove('hidden');
            }
        }

        async function stopQrScanner() {
            if (html5QrScanner && isScannerRunning) {
                await html5QrScanner.stop();
                isScannerRunning = false;
            }
            document.getElementById('cameraPlaceholder').classList.remove('hidden');
            document.getElementById('btnStartCamera').classList.remove('hidden');
            document.getElementById('btnStopCamera').classList.add('hidden');
        }

        async function renderLocalGuestList() {
            const listEl = document.getElementById('localGuestsCardList');
            if (!listEl) return;

            const query = (document.getElementById('localSearchInput')?.value || '').toLowerCase().trim();
            const guests = await localStore.getGuestsByEvent(ACTIVE_EVENT_ID);

            const filtered = guests.filter((g) => {
                if (!query) return true;
                return (g.name && g.name.toLowerCase().includes(query)) ||
                       (g.phone && g.phone.toLowerCase().includes(query)) ||
                       (g.ticket_code && g.ticket_code.toLowerCase().includes(query));
            });

            if (filtered.length === 0) {
                listEl.innerHTML = `<div class="py-8 text-center text-xs text-slate-400">Tidak ada data tamu lokal yang cocok.</div>`;
                return;
            }

            listEl.innerHTML = filtered.map((g) => `
                <div class="rounded-xl border border-slate-200 p-3.5 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 truncate">${g.name}</div>
                        <div class="text-[11px] text-slate-500 font-mono">${g.ticket_code} &bull; ${g.phone || '-'}</div>
                    </div>
                    <button type="button" onclick="switchKioskTab('scanner'); processScannedCode('${g.ticket_code}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold shrink-0 ${g.is_checked_in ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-700 text-white'}">
                        ${g.is_checked_in ? 'Sudah Hadir' : 'Check-in'}
                    </button>
                </div>
            `).join('');
        }

        async function switchKioskTab(tabName) {
            if (tabName !== 'scanner' && isScannerRunning) {
                await stopQrScanner();
            }
            ['scanner', 'guests', 'sync'].forEach((t) => {
                document.getElementById(`kioskTab_${t}`).classList.toggle('hidden', t !== tabName);
                const btn = document.getElementById(`tabBtn_${t}`);
                btn.className = (t === tabName)
                    ? 'py-2 rounded-lg font-semibold bg-slate-900 text-white transition'
                    : 'py-2 rounded-lg font-semibold text-slate-600 hover:text-slate-900 transition';
            });
            if (tabName === 'guests') renderLocalGuestList();
        }

        function playBeep(freq = 880, duration = 120) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, ctx.currentTime);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + duration / 1000);
            } catch (e) {}
        }
    </script>
@endif
@endsection
