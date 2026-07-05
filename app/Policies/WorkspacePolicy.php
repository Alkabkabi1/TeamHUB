<?php

namespace App\Policies;

use App\Enums\WorkspaceCapability;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->isAdmin()
            || $user->hasWorkspaceCapability(WorkspaceCapability::ManageWorkspace, $workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Workspace $workspace): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return false;
    }
}
