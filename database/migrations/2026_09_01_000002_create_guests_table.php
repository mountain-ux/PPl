<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel guests / tamu (FR-02, FR-03, FR-05, FR-06)
     */
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('phone', 100)->nullable()->comment('Nomor WhatsApp / HP aktif');
            $table->string('email', 100)->nullable();
            $table->json('custom_data')->nullable()->comment('Jawaban tamu untuk field kustom Event');
            $table->string('ticket_code', 50)->unique()->comment('Kode tiket singkat mis. TMN-2026-XXXXX untuk input manual');
            $table->string('qr_code', 255)->unique()->nullable()->comment('Payload QR unik bertanda tangan HMAC; null jika QR belum terbit');
            $table->enum('source', ['mandiri', 'admin', 'impor'])->default('mandiri');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();

            // Index pencarian tamu per Event & pencegahan duplikasi kontak di Event yang sama
            $table->index(['event_id', 'phone']);
            $table->index(['event_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
