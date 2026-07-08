<?php

namespace App\Policies;

use App\Enums\ProjectCapability;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, ?Project $project = null): bool
    {
        if ($project === null) {
            return $user->isAdmin()
                || $user->projectMemberships()
                    ->where('status', 'approved')
                    ->exists();
        }

        return $this->isProjectMember($user, $project);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->isProjectMember($user, $task->project);
    }

    public function create(User $user, ?Project $project = null): bool
    {
        if ($project === null) {
            return false;
        }

        return $user->hasProjectCapability(ProjectCapability::ManageProject, $project);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasProjectCapability(ProjectCapability::ManageProject, $task->project)
            || $task->isAssignedTo($user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasProjectCapability(ProjectCapability::ManageProject, $task->project);
    }

    public function submitDeliverable(User $user, Task $task): bool
    {
        return $user->hasProjectCapability(ProjectCapability::ManageProject, $task->project)
            || $task->isAssignedTo($user);
    }

    public function approveDeliverable(User $user, Task $task): bool
    {
        return $user->hasProjectCapability(ProjectCapability::ManageProject, $task->project);
    }

    public function requestChanges(User $user, Task $task): bool
    {
        return $this->approveDeliverable($user, $task);
    }

    private function isProjectMember(User $user, Project $project): bool
    {
        return $user->projectMemberships()
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->exists();
    }
}
