/**
 * ============================================================================
 * TAMUIN Client-Side Offline-First Engine (SDD Bab III.J Class Diagram Hal. 67)
 * Mengimplementasikan 3 Class Klien:
 * 1. LocalStore      : Mengelola database lokal IndexedDB (guests & attendances)
 * 2. CheckInService  : Validasi QR Code & Kode Tiket secara offline (< 2 detik) + Deteksi Duplikasi
 * 3. SyncClient      : Pemantau status koneksi (online/offline) & Sinkronisasi ke Server
 * ============================================================================
 */

class LocalStore {
    constructor(dbName = 'TamuinKioskDB', version = 1) {
        this.dbName = dbName;
        this.version = version;
        this.db = null;
    }

    async open() {
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Store 1: guests (Data tamu yang diunduh dari server per Event)
                if (!db.objectStoreNames.contains('guests')) {
                    const guestStore = db.createObjectStore('guests', { keyPath: 'id' });
                    guestStore.createIndex('by_event', 'event_id', { unique: false });
                    guestStore.createIndex('by_qr', 'qr_code', { unique: false });
                    guestStore.createIndex('by_ticket', 'ticket_code', { unique: false });
                }

                // Store 2: attendances (Catatan kehadiran lokal yang direkam di kiosk)
                if (!db.objectStoreNames.contains('attendances')) {
                    const attStore = db.createObjectStore('attendances', { keyPath: 'local_uuid' });
                    attStore.createIndex('by_event', 'event_id', { unique: false });
                    attStore.createIndex('by_guest', 'guest_id', { unique: true });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Simpan seluruh data tamu Event ke IndexedDB tanpa duplikat (FR-07, UC-07, NFR-08)
     * Sekaligus mempertahankan catatan check-in lokal yang belum tersinkron (NFR-12).
     */
    async saveGuests(eventId, guests) {
        const db = await this.open();
        const unsyncedList = await this.getUnsynced(eventId);
        const unsyncedByGuestId = new Map();
        unsyncedList.forEach((item) => unsyncedByGuestId.set(Number(item.guest_id), item));

        return new Promise((resolve, reject) => {
            const tx = db.transaction(['guests'], 'readwrite');
            const store = tx.objectStore('guests');
            const idx = store.index('by_event');

            // Hapus entri tamu lama untuk event ini terlebih dahulu agar bersih (NFR-08)
            const clearReq = idx.openKeyCursor(IDBKeyRange.only(Number(eventId)));
            clearReq.onsuccess = (e) => {
                const cursor = e.target.result;
                if (cursor) {
                    store.delete(cursor.primaryKey);
                    cursor.continue();
                } else {
                    // Masukkan data tamu terbaru dengan put() (idempotent)
                    guests.forEach((g) => {
                        const localPending = unsyncedByGuestId.get(Number(g.id));
                        store.put({
                            ...g,
                            event_id: Number(g.event_id),
                            is_checked_in: Boolean(g.is_checked_in || localPending),
                            checked_in_at: localPending ? localPending.checked_in_at : g.checked_in_at,
                            local_uuid: localPending ? localPending.local_uuid : g.local_uuid,
                        });
                    });
                }
            };

            tx.oncomplete = () => resolve(guests.length);
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Cari tamu berdasarkan QR Code atau Kode Tiket secara lokal di IndexedDB (FR-09, UC-09, NFR-10)
     */
    async findByQrOrTicket(rawCode, eventId) {
        const db = await this.open();
        const code = String(rawCode || '').trim();
        if (!code) return null;

        return new Promise((resolve, reject) => {
            const tx = db.transaction(['guests'], 'readonly');
            const store = tx.objectStore('guests');

            // 1. Cek pada index QR Code
            const qrReq = store.index('by_qr').getAll(IDBKeyRange.only(code));
            qrReq.onsuccess = () => {
                const qrMatches = (qrReq.result || []).filter((g) => Number(g.event_id) === Number(eventId));
                if (qrMatches.length > 0) {
                    resolve(qrMatches[0]);
                    return;
                }

                // 2. Jika bukan payload penuh, cek pada index ticket_code (untuk input manual mis. TMN-2026-88421)
                const normalizedTicket = code.replace(/^#/, '').toUpperCase();
                const ticketReq = store.index('by_ticket').getAll(IDBKeyRange.only(normalizedTicket));
                ticketReq.onsuccess = () => {
                    const ticketMatches = (ticketReq.result || []).filter((g) => Number(g.event_id) === Number(eventId));
                    resolve(ticketMatches.length > 0 ? ticketMatches[0] : null);
                };
                ticketReq.onerror = () => reject(ticketReq.error);
            };
            qrReq.onerror = () => reject(qrReq.error);
        });
    }

    /**
     * Ambil semua tamu untuk Event aktif dari IndexedDB (untuk pencarian manual Nama/WA & statistik)
     */
    async getGuestsByEvent(eventId) {
        const db = await this.open();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['guests'], 'readonly');
            const store = tx.objectStore('guests');
            const req = store.index('by_event').getAll(IDBKeyRange.only(Number(eventId)));
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    /**
     * Simpan status kehadiran tamu beserta waktu check-in ke IndexedDB (FR-10, UC-10, NFR-12)
     */
    async saveAttendance(guest, scanMethod = 'offline', deviceId = 'KIOSK-STATION-01') {
        const db = await this.open();
        const nowStr = new Date().toISOString().slice(0, 19).replace('T', ' ');
        const localUuid = (crypto && crypto.randomUUID)
            ? crypto.randomUUID()
            : ('uuid-' + Date.now() + '-' + Math.random().toString(36).substring(2, 10));

        const attendanceRecord = {
            local_uuid: guest.local_uuid || localUuid,
            event_id: Number(guest.event_id),
            guest_id: Number(guest.id),
            guest_name: guest.name,
            ticket_code: guest.ticket_code,
            qr_code: guest.qr_code || '',
            checked_in_at: nowStr,
            scan_method: scanMethod,
            device_id: deviceId,
            is_synced: 0,
        };

        return new Promise((resolve, reject) => {
            const tx = db.transaction(['guests', 'attendances'], 'readwrite');
            const guestStore = tx.objectStore('guests');
            const attStore = tx.objectStore('attendances');

            const updatedGuest = {
                ...guest,
                is_checked_in: true,
                checked_in_at: nowStr,
                local_uuid: attendanceRecord.local_uuid,
            };

            guestStore.put(updatedGuest);
            attStore.put(attendanceRecord);

            tx.oncomplete = () => resolve({ guest: updatedGuest, attendance: attendanceRecord });
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Ambil seluruh data check-in lokal yang belum tersinkron ke server (UC-12 & UC-15)
     */
    async getUnsynced(eventId) {
        const db = await this.open();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['attendances'], 'readonly');
            const store = tx.objectStore('attendances');
            const req = store.index('by_event').getAll(IDBKeyRange.only(Number(eventId)));
            req.onsuccess = () => {
                const all = req.result || [];
                resolve(all.filter((a) => Number(a.is_synced) === 0));
            };
            req.onerror = () => reject(req.error);
        });
    }

    /**
     * Tandai daftar local_uuid sebagai sudah tersinkronisasi (UC-12 Postcondition)
     */
    async markSynced(localUuids = []) {
        if (!localUuids.length) return 0;
        const db = await this.open();

        return new Promise((resolve, reject) => {
            const tx = db.transaction(['attendances'], 'readwrite');
            const store = tx.objectStore('attendances');

            localUuids.forEach((uuid) => {
                const getReq = store.get(uuid);
                getReq.onsuccess = () => {
                    const rec = getReq.result;
                    if (rec) {
                        rec.is_synced = 1;
                        rec.synced_at = new Date().toISOString();
                        store.put(rec);
                    }
                };
            });

            tx.oncomplete = () => resolve(localUuids.length);
            tx.onerror = () => reject(tx.error);
        });
    }
}

class CheckInService {
    constructor(localStore, activeEventId) {
        this.localStore = localStore;
        this.activeEventId = Number(activeEventId);
    }

    /**
     * Validasi QR Code atau Kode Tiket terhadap data lokal di IndexedDB (FR-09, UC-09, NFR-10 < 2 detik)
     */
    async validate(rawCode) {
        const startTime = performance.now();
        const code = String(rawCode || '').trim();

        // Cek apakah format QR adalah milik Event lain (mis. TMN1|{other_event_id}|...)
        if (code.startsWith('TMN1|')) {
            const parts = code.split('|');
            if (parts.length === 5 && Number(parts[1]) !== this.activeEventId) {
                return {
                    status: 'wrong_event',
                    code: code,
                    latency_ms: Math.round(performance.now() - startTime),
                    message: `QR Code ini berasal dari Event yang berbeda (ID Event #${parts[1]}), bukan Event aktif saat ini.`,
                };
            }
        }

        const guest = await this.localStore.findByQrOrTicket(code, this.activeEventId);
        const latencyMs = Math.max(1, Math.round(performance.now() - startTime));

        if (!guest) {
            return {
                status: 'not_found',
                code: code,
                latency_ms: latencyMs,
                message: 'Data tiket tidak cocok dengan basis data peserta pada Event aktif ini.',
            };
        }

        // Deteksi Duplikasi Check-in (FR-11, UC-11, NFR-14)
        if (this.detectDuplicate(guest)) {
            return {
                status: 'duplicate',
                guest: guest,
                code: code,
                latency_ms: latencyMs,
                message: 'QR Code ini telah tercatat hadir sebelumnya pada sistem presensi.',
            };
        }

        return {
            status: 'valid',
            guest: guest,
            code: code,
            latency_ms: latencyMs,
        };
    }

    detectDuplicate(guest) {
        return Boolean(guest && guest.is_checked_in);
    }

    async confirm(guest, isOnline = false, deviceId = 'KIOSK-STATION-01') {
        const method = isOnline ? 'online' : 'offline';
        return await this.localStore.saveAttendance(guest, method, deviceId);
    }
}

class SyncClient {
    constructor(localStore, config) {
        this.localStore = localStore;
        this.eventId = Number(config.eventId);
        this.pullUrl = config.pullUrl;
        this.pushUrl = config.pushUrl;
        this.csrfToken = config.csrfToken;
        this.simulatedOffline = false;
        this.isSyncing = false;
        this.onStatusChange = config.onStatusChange || (() => {});
    }

    get isOnline() {
        if (this.simulatedOffline) return false;
        return navigator.onLine;
    }

    setSimulatedOffline(flag) {
        this.simulatedOffline = Boolean(flag);
        this.notifyStatus();
        if (this.isOnline) {
            this.pushUnsynced();
        }
    }

    watchConnection() {
        window.addEventListener('online', () => {
            this.notifyStatus();
            if (this.isOnline) {
                this.pushUnsynced();
            }
        });

        window.addEventListener('offline', () => {
            this.notifyStatus();
        });

        this.notifyStatus();
    }

    async notifyStatus(extra = {}) {
        const guests = await this.localStore.getGuestsByEvent(this.eventId);
        const unsynced = await this.localStore.getUnsynced(this.eventId);
        const checkedInCount = guests.filter((g) => g.is_checked_in).length;

        this.onStatusChange({
            isOnline: this.isOnline,
            simulatedOffline: this.simulatedOffline,
            isSyncing: this.isSyncing,
            totalLocalGuests: guests.length,
            checkedInCount: checkedInCount,
            unsyncedCount: unsynced.length,
            ...extra,
        });
    }

    /**
     * Unduh seluruh data tamu Event aktif dari server ke IndexedDB (FR-07 / UC-07)
     */
    async pullGuests() {
        if (!this.isOnline) {
            throw new Error('Perangkat sedang dalam mode Offline. Hubungkan ke internet untuk mengunduh data tamu awal.');
        }

        const response = await fetch(this.pullUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Gagal mengunduh data tamu (HTTP ${response.status}).`);
        }

        const data = await response.json();
        const savedCount = await this.localStore.saveGuests(this.eventId, data.guests || []);
        localStorage.setItem(`tamuin_last_pull_${this.eventId}`, new Date().toLocaleTimeString('id-ID'));

        await this.notifyStatus({ lastPullCount: savedCount });
        return data;
    }

    /**
     * Kirim antrean data check-in lokal yang belum tersinkron ke server MySQL (FR-12 / UC-12)
     */
    async pushUnsynced() {
        if (!this.isOnline || this.isSyncing) {
            return { synced: 0, skipped: true };
        }

        const unsynced = await this.localStore.getUnsynced(this.eventId);
        if (unsynced.length === 0) {
            await this.notifyStatus();
            return { synced: 0, message: 'Seluruh data lokal sudah tersinkronisasi.' };
        }

        this.isSyncing = true;
        await this.notifyStatus();

        try {
            const response = await fetch(this.pushUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ attendances: unsynced }),
            });

            if (!response.ok) {
                throw new Error(`Server merespons dengan status ${response.status}`);
            }

            const payload = await response.json();
            const syncedUuids = (payload.result && payload.result.synced_uuids) ? payload.result.synced_uuids : [];
            await this.localStore.markSynced(syncedUuids);

            localStorage.setItem(`tamuin_last_sync_${this.eventId}`, new Date().toLocaleTimeString('id-ID'));
            this.isSyncing = false;
            await this.notifyStatus({ lastSyncSuccess: true });

            return {
                synced: syncedUuids.length,
                result: payload.result,
            };
        } catch (err) {
            this.isSyncing = false;
            await this.notifyStatus({ lastSyncError: err.message });
            throw err;
        }
    }
}

window.TamuinKiosk = { LocalStore, CheckInService, SyncClient };
