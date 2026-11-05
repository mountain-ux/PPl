<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel attendances / kehadiran (FR-10, FR-11, FR-12)
     * Catatan: guest_id UNIQUE (1 tamu maksimal 1 kehadiran), checked_in_by TIDAK UNIQUE (1 petugas bisa scan banyak tamu).
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')
                ->unique()
                ->constrained('guests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('checked_in_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->dateTime('checked_in_at')->comment('Waktu check-in menurut perangkat kiosk');
            $table->enum('scan_method', ['online', 'offline'])->default('offline');
            $table->string('device_id', 100)->nullable()->comment('Identitas perangkat kiosk mis. KIOSK-STATION-01');
            $table->string('local_uuid', 64)->unique()->comment('UUID unik dari IndexedDB untuk mencegah duplikasi saat sinkronisasi');
            $table->enum('sync_status', ['berhasil', 'gagal'])->default('berhasil');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
