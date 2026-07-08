<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function workspacePolicyLead(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $lead = User::factory()->student()->create();

    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    return [$lead, $workspace];
}

function workspacePolicyMember(Workspace $workspace): User
{
    $user = User::factory()->student()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    return $user;
}

test('any authenticated user can list and view workspaces', function () {
    [$lead, $workspace] = workspacePolicyLead();
    $member = workspacePolicyMember($workspace);

    expect($lead->can('viewAny', Workspace::class))->toBeTrue()
        ->and($lead->can('view', $workspace))->toBeTrue()
        ->and($member->can('viewAny', Workspace::class))->toBeTrue()
        ->and($member->can('view', $workspace))->toBeTrue();
});

test('only platform admins can create workspaces', function () {
    [$lead] = workspacePolicyLead();
    $admin = User::factory()->admin()->create();

    expect($admin->can('create', Workspace::class))->toBeTrue()
        ->and($lead->can('create', Workspace::class))->toBeFalse();
});

test('workspace leads and admins can update a workspace', function () {
    [$lead, $workspace] = workspacePolicyLead();
    $member = workspacePolicyMember($workspace);
    $admin = User::factory()->admin()->create();

    expect($lead->can('update', $workspace))->toBeTrue()
        ->and($admin->can('update', $workspace))->toBeTrue()
        ->and($member->can('update', $workspace))->toBeFalse();
});

test('only platform admins can delete workspaces', function () {
    [$lead, $workspace] = workspacePolicyLead();
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $workspace))->toBeTrue()
        ->and($lead->can('delete', $workspace))->toBeFalse();
});

test('force delete is denied for everyone', function () {
    [$lead, $workspace] = workspacePolicyLead();
    $admin = User::factory()->admin()->create();

    expect($admin->can('forceDelete', $workspace))->toBeFalse()
        ->and($lead->can('forceDelete', $workspace))->toBeFalse();
});
