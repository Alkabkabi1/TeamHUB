<?php

namespace App\Policies;

use App\Enums\ClubCapability;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * View the public events catalog.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * View a single event. Active events are public; drafts and cancelled
     * events are only visible to users who can manage the owning club.
     */
    public function view(?User $user, Event $event): bool
    {
        if ($event->status === EventStatus::Active) {
            return true;
        }

        return $user !== null
            && $user->can(ClubCapability::ManageEvents->value, $event->club);
    }

    /**
     * Create, update or delete events — delegated to the club-scoped
     * manage-events capability (university staff bypass via Gate::before).
     */
    public function manage(User $user, Event $event): bool
    {
        return $user->can(ClubCapability::ManageEvents->value, $event->club);
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
