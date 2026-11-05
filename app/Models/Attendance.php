<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'checked_in_by',
        'checked_in_at',
        'scan_method',
        'device_id',
        'local_uuid',
        'sync_status',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Cek apakah catatan kehadiran sudah tersinkronisasi (Class Diagram SDD Bab III.J)
     */
    public function isSynced(): bool
    {
        return $this->sync_status === 'berhasil' && $this->synced_at !== null;
    }

    /**
     * Relasi ke Tamu (Guest)
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id');
    }

    /**
     * Relasi ke Petugas/Admin yang melakukan scan
     */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
