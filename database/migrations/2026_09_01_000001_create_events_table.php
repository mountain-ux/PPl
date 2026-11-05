<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel events (FR-17, FR-18, NFR-20, NFR-23)
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->date('event_date');
            $table->string('event_time', 50)->nullable()->comment('Contoh: 08:00 - 15:30 WITA');
            $table->string('location', 200);
            $table->text('description')->nullable();
            $table->json('form_fields')->nullable()->comment('Konfigurasi field kustom formulir (maks 10 field)');
            $table->string('registration_slug', 160)->unique();
            $table->boolean('is_registration_open')->default(true);
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['created_by', 'event_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
