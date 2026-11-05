<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    public const MAX_CUSTOM_FIELDS = 10; // Sesuai NFR-23

    protected $fillable = [
        'name',
        'event_date',
        'event_time',
        'location',
        'description',
        'form_fields',
        'registration_slug',
        'is_registration_open',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'form_fields' => 'array',
            'is_registration_open' => 'boolean',
        ];
    }

    /**
     * Scope query Event berdasarkan hak akses pengguna (FR-17 & NFR-20):
     * Admin dapat melihat seluruh Event, Petugas hanya Event yang dibuatnya sendiri.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    /**
     * Cek kepemilikan Event (Class Diagram SDD Bab III.J: isOwnedBy)
     */
    public function isOwnedBy(User $user): bool
    {
        return (int) $this->created_by === (int) $user->id;
    }

    /**
     * Cek apakah user memiliki izin mengakses Event ini
     */
    public function canBeAccessedBy(User $user): bool
    {
        return $user->isAdmin() || $this->isOwnedBy($user);
    }

    /**
     * Buka pendaftaran RSVP publik
     */
    public function openRegistration(): bool
    {
        return $this->update(['is_registration_open' => true]);
    }

    /**
     * Tutup pendaftaran RSVP publik
     */
    public function closeRegistration(): bool
    {
        return $this->update(['is_registration_open' => false]);
    }

    /**
     * Generate slug unik dari nama Event
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'event-tamuin';
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('registration_slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Relasi ke User pembuat Event
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi: Satu Event memiliki banyak Guest / Tamu (1 : N)
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'event_id');
    }

    /**
     * Relasi: Kehadiran pada Event ini melalui tabel guests
     */
    public function attendances(): HasManyThrough
    {
        return $this->hasManyThrough(
            Attendance::class,
            Guest::class,
            'event_id', // FK di tabel guests
            'guest_id', // FK di tabel attendances
            'id',       // PK di tabel events
            'id'        // PK di tabel guests
        );
    }
}
