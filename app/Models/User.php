<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Cek apakah user berperan sebagai Admin (Class Diagram SDD Bab III.J)
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user berperan sebagai Petugas Check-in (Class Diagram SDD Bab III.J)
     */
    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }

    /**
     * Relasi: Satu user (Admin/Petugas) dapat membuat banyak Event (1 : N)
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    /**
     * Relasi: Satu Petugas dapat mencatat banyak kehadiran tamu (1 : N)
     */
    public function recordedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'checked_in_by');
    }
}
