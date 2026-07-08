<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

test('guest is redirected to login from workspace show', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('login'));
});

test('workspace show redirects managers to workspace manage', function () {
    $workspace = Workspace::factory()->create([
        'name' => 'مساحة الحاسبات',
        'status' => 'active',
    ]);
    $lead = User::factory()->student()->create();

    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    $this->actingAs($lead)
        ->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('workspaces.manage', $workspace));
});

test('workspace show redirects members to dashboard', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $member = User::factory()->student()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($member)
        ->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('dashboard'));
});
