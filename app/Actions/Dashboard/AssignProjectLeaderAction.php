<?php

namespace App\Actions\Dashboard;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Support\DemoWalkthroughBootstrap;
use Illuminate\Validation\ValidationException;

class AssignProjectLeaderAction
{
    public function execute(User $actor, Project $project, User $leader): ProjectMembership
    {
        $project->loadMissing('workspace');

        $isApprovedWorkspaceMember = $project->workspace->memberships()
            ->where('user_id', $leader->id)
            ->where('status', 'approved')
            ->exists();

        if (! $isApprovedWorkspaceMember && ! $leader->isAdmin()) {
            throw ValidationException::withMessages([
                'leader_id' => [__('project.members.validation.not_club_member')],
            ]);
        }

        $membership = ProjectMembership::query()->firstOrCreate(
            ['user_id' => $leader->id, 'project_id' => $project->id],
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

        $membership->assignProjectRole(ProjectRole::Member);
        $membership->assignProjectRole(ProjectRole::ProjectLead);

        DemoWalkthroughBootstrap::ensureDemoStaffOnProject($project, $actor);

        return $membership;
    }
}
