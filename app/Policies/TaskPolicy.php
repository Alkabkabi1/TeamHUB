<?php

namespace App\Policies;

use App\Models\Committee;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, ?Committee $committee = null): bool
    {
        if ($committee === null) {
            return $user->isUniversityStaff();
        }

        return $this->isCommitteeMember($user, $committee);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->isCommitteeMember($user, $task->committee);
    }

    public function create(User $user, ?Committee $committee = null): bool
    {
        if ($committee === null) {
            return $user->isUniversityStaff();
        }

        return $user->canManageCommittee($committee);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->canManageCommittee($task->committee) || $task->isAssignedTo($user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->canManageCommittee($task->committee);
    }

    public function submitDeliverable(User $user, Task $task): bool
    {
        return $user->canManageCommittee($task->committee) || $task->isAssignedTo($user);
    }

    public function approveDeliverable(User $user, Task $task): bool
    {
        return $user->canManageCommittee($task->committee);
    }

    public function requestChanges(User $user, Task $task): bool
    {
        return $this->approveDeliverable($user, $task);
    }

    private function isCommitteeMember(User $user, Committee $committee): bool
    {
        return $user->canManageCommittee($committee)
            || $user->committeeMemberships()
                ->where('committee_id', $committee->id)
                ->where('status', 'approved')
                ->exists();
    }
}
