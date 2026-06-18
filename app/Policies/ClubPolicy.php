<?php

namespace App\Policies;

use App\Enums\ClubCapability;
use App\Models\Club;
use App\Models\User;

class ClubPolicy
{
    /**
     * View the public club catalog.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a single club.
     */
    public function view(User $user, Club $club): bool
    {
        return true;
    }

    /**
     * Create a club — university staff only.
     */
    public function create(User $user): bool
    {
        return $user->isUniversityStaff();
    }

    /**
     * Edit a club's core details — university staff, or a club manager who
     * holds the manage-club capability.
     */
    public function update(User $user, Club $club): bool
    {
        return $user->isUniversityStaff()
            || $user->hasClubCapability(ClubCapability::ManageClub, $club);
    }

    /**
     * Archive or delete a club — university staff only.
     */
    public function delete(User $user, Club $club): bool
    {
        return $user->isUniversityStaff();
    }

    public function restore(User $user, Club $club): bool
    {
        return $user->isUniversityStaff();
    }

    public function forceDelete(User $user, Club $club): bool
    {
        return false;
    }
}
