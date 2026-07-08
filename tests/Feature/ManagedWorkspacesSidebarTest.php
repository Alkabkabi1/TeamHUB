<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

/**
 * Attach an approved membership holding $roles, returning the workspace.
 *
 * @param  array<int, WorkspaceRole>  $roles
 */
function attachRoles(User $user, array $roles): Workspace
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles($roles);

    return $workspace;
}

test('a user managing one workspace gets a single managed_workspaces entry in shared props', function () {
    $user = User::factory()->student()->create();
    $workspace = attachRoles($user, [WorkspaceRole::WorkspaceLead]);

    $this->actingAs($user)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.managed_workspaces', 1)
            ->where('auth.user.is_workspace_lead', true)
        );
});

test('a user managing two workspaces gets two managed_workspaces entries in shared props', function () {
    $user = User::factory()->student()->create();
    $workspace = attachRoles($user, [WorkspaceRole::WorkspaceLead]);
    attachRoles($user, [WorkspaceRole::MembershipManager]);

    $this->actingAs($user)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.managed_workspaces', 2)
        );
});

test('a plain member exposes no managed workspaces', function () {
    $user = User::factory()->student()->create();
    attachRoles($user, [WorkspaceRole::Member]);

    $this->actingAs($user)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.managed_workspaces', 0)
            ->where('auth.user.is_workspace_lead', false)
        );
});
