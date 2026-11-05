<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $qrService = app(QrCodeService::class);

        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator TAMUIN',
                'email' => 'admin@unsulbar.ac.id',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $petugas1 = User::updateOrCreate(
            ['username' => 'petugas01'],
            [
                'name' => 'Aldi Salam (Gate A)',
                'email' => 'petugas01@unsulbar.ac.id',
                'password' => Hash::make('petugas123'),
                'role' => 'petugas',
                'is_active' => true,
            ]
        );

        $seminarEvent = Event::updateOrCreate(
            ['registration_slug' => 'seminar-digital-ai-skills-hub-2026'],
            [
                'name' => 'Seminar Digital & AI Skills Hub 2026 UNSULBAR x UNDP',
                'event_date' => '2026-05-05',
                'event_time' => '08:00 - 15:30 WITA',
                'location' => 'Lecture Theater Gedung A, Univ. Sulawesi Barat',
                'description' => 'Peluncuran & seminar literasi teknologi dan kecerdasan buatan hasil kolaborasi Universitas Sulawesi Barat dan UNDP.',
                'is_registration_open' => true,
                'created_by' => $admin->id,
                'form_fields' => [
                    [
                        'name' => 'kategori_tamu',
                        'label' => 'Kategori Tamu',
                        'type' => 'select',
                        'is_required' => true,
                        'options' => ['VIP / Keynote', 'Dosen & Civitas', 'Mahasiswa / Peserta', 'Media / Tamu Undangan'],
                    ],
                    [
                        'name' => 'asal_instansi',
                        'label' => 'Asal Instansi / Fakultas',
                        'type' => 'text',
                        'is_required' => true,
                        'options' => [],
                    ],
                    [
                        'name' => 'komitmen_hadir',
                        'label' => 'Konfirmasi Komitmen Kehadiran',
                        'type' => 'select',
                        'is_required' => true,
                        'options' => ['Ya, Pasti Hadir', 'Masih Ragu'],
                    ],
                ],
            ]
        );

        Event::updateOrCreate(
            ['registration_slug' => 'workshop-web-dev-informatika-c2023'],
            [
                'name' => 'Workshop Web Development Informatika Kelas C2023',
                'event_date' => '2026-11-15',
                'event_time' => '09:00 - 14:00 WITA',
                'location' => 'Laboratorium Komputer Terpadu Unsulbar',
                'description' => 'Workshop internal pengembangan Progressive Web App dengan Laravel dan Tailwind CSS.',
                'is_registration_open' => true,
                'created_by' => $petugas1->id,
                'form_fields' => [
                    [
                        'name' => 'nim',
                        'label' => 'NIM Mahasiswa',
                        'type' => 'text',
                        'is_required' => true,
                        'options' => [],
                    ],
                    [
                        'name' => 'angkatan',
                        'label' => 'Angkatan',
                        'type' => 'select',
                        'is_required' => true,
                        'options' => ['2022', '2023', '2024', '2025'],
                    ],
                ],
            ]
        );

        $sampleGuests = [
            [
                'ticket_code' => 'TMN-2026-88421',
                'name' => 'Muhammad Rian Hidayat',
                'phone' => '081242108881',
                'email' => 'rian.hidayat@unsulbar.ac.id',
                'source' => 'mandiri',
                'checked_in_time' => '2026-05-05 08:55:12',
                'device' => 'Gate A • VIP',
                'custom_data' => [
                    'kategori_tamu' => 'VIP / Keynote',
                    'asal_instansi' => 'Fakultas Teknik Unsulbar',
                    'komitmen_hadir' => 'Ya, Pasti Hadir',
                ],
            ],
            [
                'ticket_code' => 'TMN-2026-88422',
                'name' => 'Nurul Fadilah',
                'phone' => '085299884412',
                'email' => 'nurul.f@student.unsulbar.ac.id',
                'source' => 'mandiri',
                'checked_in_time' => '2026-05-05 09:05:40',
                'device' => 'Gate B • Reguler',
                'custom_data' => [
                    'kategori_tamu' => 'Mahasiswa / Peserta',
                    'asal_instansi' => 'Informatika Unsulbar',
                    'komitmen_hadir' => 'Ya, Pasti Hadir',
                ],
            ],
            [
                'ticket_code' => 'TMN-2026-88423',
                'name' => 'Dr. Hj. Andy Rita Mariani, M.Pd.',
                'phone' => '08114122003',
                'email' => 'andy.rita@pemkabmajene.go.id',
                'source' => 'admin',
                'checked_in_time' => '2026-05-05 08:22:10',
                'device' => 'Gate VIP',
                'custom_data' => [
                    'kategori_tamu' => 'VIP / Keynote',
                    'asal_instansi' => 'Pemkab Majene',
                    'komitmen_hadir' => 'Ya, Pasti Hadir',
                ],
            ],
            [
                'ticket_code' => 'TMN-2026-88424',
                'name' => 'Andi M. Fadhil',
                'phone' => '082188415509',
                'email' => 'fadhil.andi@unsulbar.ac.id',
                'source' => 'mandiri',
                'checked_in_time' => '2026-05-05 09:12:33',
                'device' => 'Gate A',
                'custom_data' => [
                    'kategori_tamu' => 'Mahasiswa / Peserta',
                    'asal_instansi' => 'FTIK Unsulbar',
                    'komitmen_hadir' => 'Ya, Pasti Hadir',
                ],
            ],
            [
                'ticket_code' => 'TMN-2026-88425',
                'name' => 'Putri Lestari, S.Kom., M.T.',
                'phone' => '081377119088',
                'email' => 'putri.lestari@unsulbar.ac.id',
                'source' => 'impor',
                'checked_in_time' => null,
                'device' => null,
                'custom_data' => [
                    'kategori_tamu' => 'Dosen & Civitas',
                    'asal_instansi' => 'Dosen Unsulbar',
                    'komitmen_hadir' => 'Ya, Pasti Hadir',
                ],
            ],
            [
                'ticket_code' => 'TMN-2026-88426',
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'budi.santoso@unsulbar.ac.id',
                'source' => 'mandiri',
                'checked_in_time' => null,
                'device' => null,
                'custom_data' => [
                    'kategori_tamu' => 'Dosen & Civitas',
                    'asal_instansi' => 'Universitas Sulawesi Barat',
                    'komitmen_hadir' => 'Ya, Pasti Hadir',
                ],
            ],
        ];

        foreach ($sampleGuests as $gData) {
            $guest = Guest::updateOrCreate(
                ['ticket_code' => $gData['ticket_code']],
                [
                    'event_id' => $seminarEvent->id,
                    'name' => $gData['name'],
                    'phone' => $gData['phone'],
                    'email' => $gData['email'],
                    'source' => $gData['source'],
                    'custom_data' => $gData['custom_data'],
                    'registered_at' => now()->subHours(rand(2, 36)),
                ]
            );

            if (empty($guest->qr_code)) {
                $guest->update(['qr_code' => $qrService->generate($guest)]);
            }

            if (!empty($gData['checked_in_time'])) {
                Attendance::updateOrCreate(
                    ['guest_id' => $guest->id],
                    [
                        'checked_in_by' => $petugas1->id,
                        'checked_in_at' => Carbon::parse($gData['checked_in_time']),
                        'scan_method' => 'offline',
                        'device_id' => $gData['device'] ?? 'Gate A',
                        'local_uuid' => 'seed-uuid-' . strtolower($guest->ticket_code),
                        'sync_status' => 'berhasil',
                        'synced_at' => now(),
                    ]
                );
            }
        }
    }
}
