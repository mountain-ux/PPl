<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncService
{
    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Menerima batch data kehadiran dari perangkat Kiosk (IndexedDB) dan menggabungkannya
     * ke database MySQL pusat tanpa duplikasi menggunakan local_uuid & guest_id (FR-12, UC-12, NFR-15).
     */
    public function receive(Event $event, array $records, User $operator): array
    {
        $syncedUuids = [];
        $newlyInserted = 0;
        $alreadyExisted = 0;
        $rejected = [];

        DB::transaction(function () use ($event, $records, $operator, &$syncedUuids, &$newlyInserted, &$alreadyExisted, &$rejected) {
            foreach ($records as $item) {
                $localUuid = trim((string) ($item['local_uuid'] ?? ''));
                $guestId = (int) ($item['guest_id'] ?? 0);
                $qrCode = trim((string) ($item['qr_code'] ?? ''));
                $checkedInAtRaw = $item['checked_in_at'] ?? now()->toDateTimeString();
                $scanMethod = in_array($item['scan_method'] ?? 'offline', ['online', 'offline'], true)
                    ? $item['scan_method']
                    : 'offline';
                $deviceId = substr(trim((string) ($item['device_id'] ?? 'KIOSK-STATION-01')), 0, 100);

                if ($localUuid === '' || $guestId <= 0) {
                    $rejected[] = [
                        'local_uuid' => $localUuid,
                        'reason' => 'Data local_uuid atau guest_id tidak lengkap.',
                    ];
                    continue;
                }

                // 1. Cek apakah local_uuid ini sudah pernah disinkronkan sebelumnya (Idempotent / Anti-Duplikasi)
                $existingByUuid = Attendance::where('local_uuid', $localUuid)->first();
                if ($existingByUuid) {
                    $syncedUuids[] = $localUuid;
                    $alreadyExisted++;
                    continue;
                }

                // 2. Pastikan tamu benar-benar terdaftar pada Event aktif tersebut
                $guest = Guest::where('id', $guestId)
                    ->where('event_id', $event->id)
                    ->first();

                if (!$guest) {
                    $rejected[] = [
                        'local_uuid' => $localUuid,
                        'reason' => "Tamu ID #{$guestId} tidak ditemukan pada Event ini.",
                    ];
                    continue;
                }

                // 3. Verifikasi integritas tanda tangan QR jika dikirimkan (Bab V Security SDD Hal. 96)
                if ($qrCode !== '' && $guest->qr_code !== null && $qrCode !== $guest->qr_code) {
                    $verified = $this->qrCodeService->verify($qrCode, $event->id);
                    if (!$verified || $verified['guest_id'] !== $guest->id) {
                        $rejected[] = [
                            'local_uuid' => $localUuid,
                            'reason' => 'Tanda tangan QR Code tidak valid.',
                        ];
                        continue;
                    }
                }

                // 4. Gabungkan berdasarkan guest_id (1 tamu maksimal 1 catatan kehadiran sesuai ERD 1 : 0..1)
                $existingByGuest = Attendance::where('guest_id', $guest->id)->first();
                if ($existingByGuest) {
                    // Tamu sudah tercatat hadir sebelumnya; tandai UUID lokal sebagai tersinkron agar antrean kiosk bersih
                    $syncedUuids[] = $localUuid;
                    $alreadyExisted++;
                    continue;
                }

                try {
                    $checkedInAt = Carbon::parse($checkedInAtRaw);
                } catch (\Throwable $e) {
                    $checkedInAt = now();
                }

                Attendance::create([
                    'guest_id' => $guest->id,
                    'checked_in_by' => $operator->id,
                    'checked_in_at' => $checkedInAt,
                    'scan_method' => $scanMethod,
                    'device_id' => $deviceId,
                    'local_uuid' => $localUuid,
                    'sync_status' => 'berhasil',
                    'synced_at' => now(),
                ]);

                $syncedUuids[] = $localUuid;
                $newlyInserted++;
            }
        });

        return [
            'synced_uuids' => $syncedUuids,
            'newly_inserted' => $newlyInserted,
            'already_existed' => $alreadyExisted,
            'rejected' => $rejected,
            'server_time' => now()->toIso8601String(),
        ];
    }
}
