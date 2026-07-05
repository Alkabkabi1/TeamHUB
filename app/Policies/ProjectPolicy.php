<?php

namespace App\Policies;

use App\Enums\ProjectCapability;
use App\Enums\WorkspaceCapability;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return true;
    }

    public function create(User $user, Project $project): bool
    {
        return $user->isAdmin()
            || $user->hasWorkspaceCapability(WorkspaceCapability::ManageWorkspace, $project->workspace);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasProjectCapability(ProjectCapability::ManageProject, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin()
            || $user->hasWorkspaceCapability(WorkspaceCapability::ManageWorkspace, $project->workspace);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
