<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Menegakkan aturan akses sesuai FR-17, NFR-20, dan SDD Bab III.J:
     * Admin dapat melihat seluruh Event, Petugas hanya Event yang dibuatnya sendiri.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isPetugas());
    }

    public function view(User $user, Event $event): bool
    {
        return $user->is_active && $event->canBeAccessedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isPetugas());
    }

    public function manage(User $user, Event $event): bool
    {
        return $user->is_active && $event->canBeAccessedBy($user);
    }

    public function update(User $user, Event $event): bool
    {
        return $this->manage($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->manage($user, $event);
    }
}
