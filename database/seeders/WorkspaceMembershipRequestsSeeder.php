<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Database\Seeder;

class WorkspaceMembershipRequestsSeeder extends Seeder
{
    public function run(): void
    {
        $applicants = User::query()
            ->where('role', 'member')
            ->whereDoesntHave('membershipRequests')
            ->limit(6)
            ->get();

        $workspaces = Workspace::query()
            ->where('status', 'active')
            ->where('name', '!=', 'مساحة الحاسبات')
            ->limit(3)
            ->get();

        foreach ($applicants as $index => $user) {
            $workspace = $workspaces[$index % max($workspaces->count(), 1)] ?? null;
            if (! $workspace) {
                continue;
            }

            if ($user->workspaceMemberships()->where('workspace_id', $workspace->id)->exists()) {
                continue;
            }

            WorkspaceMembershipRequest::factory()->pending()->create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
                'full_name' => $user->name,
            ]);
        }

        $csWorkspace = Workspace::query()->where('name', 'مساحة الحاسبات')->first();
        $reviewer = User::query()->where('email', 'workspace-lead@teamhub.test')->first();

        if (! $csWorkspace) {
            return;
        }

        $reviewedApplicants = User::query()
            ->where('role', 'member')
            ->whereDoesntHave('membershipRequests', fn ($query) => $query->where('workspace_id', $csWorkspace->id))
            ->limit(4)
            ->get();

        foreach ($reviewedApplicants->take(2) as $user) {
            WorkspaceMembershipRequest::factory()->rejected()->create([
                'user_id' => $user->id,
                'workspace_id' => $csWorkspace->id,
                'full_name' => $user->name,
                'reviewed_by' => $reviewer?->id,
            ]);
        }

        foreach ($reviewedApplicants->skip(2)->take(2) as $user) {
            WorkspaceMembershipRequest::factory()->approved()->create([
                'user_id' => $user->id,
                'workspace_id' => $csWorkspace->id,
                'full_name' => $user->name,
                'reviewed_by' => $reviewer?->id,
            ]);
        }

        $pendingApplicants = User::query()
            ->where('role', 'member')
            ->where('email', '!=', 'student@teamhub.test')
            ->whereDoesntHave('membershipRequests', fn ($query) => $query->where('workspace_id', $csWorkspace->id))
            ->whereDoesntHave('workspaceMemberships', fn ($query) => $query->where('workspace_id', $csWorkspace->id))
            ->limit(3)
            ->get();

        foreach ($pendingApplicants as $user) {
            WorkspaceMembershipRequest::factory()->pending()->create([
                'user_id' => $user->id,
                'workspace_id' => $csWorkspace->id,
                'full_name' => $user->name,
            ]);
        }
    }
}
