<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'phone',
        'email',
        'custom_data',
        'ticket_code',
        'qr_code',
        'source',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_data' => 'array',
            'registered_at' => 'datetime',
        ];
    }

    /**
     * Cek apakah QR Code tamu sudah berhasil terbit (UC-03 Exception Flow)
     */
    public function hasIssuedQr(): bool
    {
        return !empty($this->qr_code);
    }

    /**
     * Cek apakah tamu sudah melakukan check-in
     */
    public function isCheckedIn(): bool
    {
        return $this->attendance()->exists();
    }

    /**
     * Relasi ke Event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Relasi: Satu Guest memiliki paling banyak satu Attendance (1 : 0..1)
     */
    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'guest_id');
    }
}
