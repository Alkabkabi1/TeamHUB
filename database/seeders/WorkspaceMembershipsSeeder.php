<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Database\Seeder;

class WorkspaceMembershipsSeeder extends Seeder
{
    public function run(): void
    {
        $workspaceLeader = User::query()->where('email', 'workspace-lead@teamhub.test')->first();
        $projectLeader = User::query()->where('email', 'project-lead@teamhub.test')->first();
        $staffMember = User::query()->where('email', 'staff@teamhub.test')->first();
        $student = User::query()->where('email', 'student@teamhub.test')->first();
        $member = User::query()->where('email', 'member@teamhub.test')->first();
        $csWorkspace = Workspace::query()->where('name', 'مساحة الحاسبات')->first();

        if ($workspaceLeader && $csWorkspace) {
            $this->seedMembership($workspaceLeader, $csWorkspace, [WorkspaceRole::WorkspaceLead], now()->subYears(2));
        }

        if ($projectLeader && $csWorkspace) {
            $this->seedMembership($projectLeader, $csWorkspace, [WorkspaceRole::Member], now()->subYear());
        }

        if ($member && $csWorkspace) {
            $this->seedMembership($member, $csWorkspace, [WorkspaceRole::Member], now()->subMonths(8));
        }

        if ($staffMember && $csWorkspace) {
            $this->seedMembership($staffMember, $csWorkspace, [WorkspaceRole::Member], now()->subMonths(6));
        }

        $environmentWorkspace = Workspace::query()->where('name', 'مساحة البيئة')->first();
        if ($workspaceLeader && $environmentWorkspace) {
            $this->seedMembership($workspaceLeader, $environmentWorkspace, [WorkspaceRole::Member], now()->subYear());
        }

        if ($student) {
            foreach (['مساحة الحاسبات', 'مساحة البيئة', 'مساحة الفنون'] as $workspaceName) {
                $workspace = Workspace::query()->where('name', $workspaceName)->first();
                if ($workspace) {
                    $this->seedMembership($student, $workspace, [WorkspaceRole::Member], now()->subMonths(fake()->numberBetween(6, 24)));
                }
            }
        }

        $members = User::query()
            ->where('role', 'member')
            ->whereNotIn('email', [
                'student@teamhub.test',
                'member@teamhub.test',
                'workspace-lead@teamhub.test',
                'project-lead@teamhub.test',
                'staff@teamhub.test',
            ])
            ->limit(12)
            ->get();

        $activeWorkspaces = Workspace::query()->where('status', 'active')->get();

        foreach ($activeWorkspaces as $workspace) {
            $memberCount = $workspace->name === 'مساحة الحاسبات' ? 8 : fake()->numberBetween(3, 6);
            $picked = $members->random(min($memberCount, $members->count()));

            foreach ($picked as $user) {
                $roles = fake()->boolean(25) ? [WorkspaceRole::MembershipManager] : [WorkspaceRole::Member];
                $this->seedMembership($user, $workspace, $roles, now()->subMonths(fake()->numberBetween(1, 36)));
            }
        }

        if ($csWorkspace) {
            $pendingMembers = User::query()
                ->where('role', 'member')
                ->where('email', '!=', 'student@teamhub.test')
                ->whereDoesntHave('workspaceMemberships', fn ($query) => $query->where('workspace_id', $csWorkspace->id))
                ->limit(3)
                ->get();

            foreach ($pendingMembers as $user) {
                WorkspaceMembership::factory()->pending()->create([
                    'user_id' => $user->id,
                    'workspace_id' => $csWorkspace->id,
                ]);
            }
        }
    }

    /**
     * @param  array<int, WorkspaceRole>  $roles
     */
    private function seedMembership(User $user, Workspace $workspace, array $roles, \DateTimeInterface $joinedAt): void
    {
        $membership = WorkspaceMembership::firstOrCreate(
            ['user_id' => $user->id, 'workspace_id' => $workspace->id],
            [
                'status' => 'approved',
                'requested_at' => $joinedAt,
                'reviewed_at' => $joinedAt,
                'joined_at' => $joinedAt,
            ],
        );

        if (! in_array(WorkspaceRole::Member, $roles, true)) {
            $roles[] = WorkspaceRole::Member;
        }

        foreach ($roles as $role) {
            $membership->assignWorkspaceRole($role);
        }
    }
}
