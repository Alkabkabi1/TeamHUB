<?php

namespace App\Actions\Dashboard;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

class AssignWorkspaceLeaderAction
{
    public function execute(User $actor, Workspace $workspace, User $leader): WorkspaceMembership
    {
        $membership = WorkspaceMembership::query()->firstOrCreate(
            ['user_id' => $leader->id, 'workspace_id' => $workspace->id],
            [
                'status' => 'approved',
                'requested_at' => now(),
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ],
        );

        if ($membership->status !== 'approved') {
            $membership->forceFill([
                'status' => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'joined_at' => now(),
            ])->save();
        }

        $membership->assignWorkspaceRole(WorkspaceRole::Member);
        $membership->assignWorkspaceRole(WorkspaceRole::WorkspaceLead);

        return $membership;
    }
}
