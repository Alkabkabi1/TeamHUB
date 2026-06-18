<?php

namespace App\Policies;

use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Models\Committee;
use App\Models\User;

class CommitteePolicy
{
    /**
     * View the public committee listing.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a single committee's public page.
     */
    public function view(User $user, Committee $committee): bool
    {
        return true;
    }

    /**
     * Create a committee — university staff, or a club lead (manage-club) of the
     * parent club. The controller passes the parent club explicitly.
     */
    public function create(User $user, Committee $committee): bool
    {
        return $user->isUniversityStaff()
            || $user->hasClubCapability(ClubCapability::ManageClub, $committee->club);
    }

    /**
     * Edit a committee's core details — anyone holding the committee's
     * manage-committee capability (staff and parent-club leads inherit it).
     */
    public function update(User $user, Committee $committee): bool
    {
        return $user->hasCommitteeCapability(CommitteeCapability::ManageCommittee, $committee);
    }

    /**
     * Archive (soft-delete) a committee — university staff or a parent-club lead.
     */
    public function delete(User $user, Committee $committee): bool
    {
        return $user->isUniversityStaff()
            || $user->hasClubCapability(ClubCapability::ManageClub, $committee->club);
    }

    public function restore(User $user, Committee $committee): bool
    {
        return $this->delete($user, $committee);
    }

    public function forceDelete(User $user, Committee $committee): bool
    {
        return false;
    }
}
