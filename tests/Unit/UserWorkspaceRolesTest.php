<?php

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, WorkspaceRole>  $roles
 */
function membership(User $user, Workspace $workspace, array $roles): WorkspaceMembership
{
    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles($roles);

    return $membership;
}

test('managedWorkspaces returns only workspaces where the user holds a manager role', function () {
    $user = User::factory()->student()->create();
    $ledClub = Workspace::factory()->create(['status' => 'active']);
    $memberClub = Workspace::factory()->create(['status' => 'active']);

    membership($user, $ledClub, [WorkspaceRole::WorkspaceLead]);
    membership($user, $memberClub, [WorkspaceRole::Member]);

    $managed = $user->managedWorkspaces();

    expect($managed)->toHaveCount(1)
        ->and($managed->first()->id)->toBe($ledClub->id);
});

test('canManageWorkspace is true for managers and admins, false otherwise', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $lead = User::factory()->student()->create();
    membership($lead, $workspace, [WorkspaceRole::WorkspaceLead]);

    $member = User::factory()->student()->create();
    membership($member, $workspace, [WorkspaceRole::Member]);

    $outsider = User::factory()->student()->create();
    $staff = User::factory()->universityStaff()->create();

    expect($lead->canManageWorkspace($workspace))->toBeTrue()
        ->and($staff->canManageWorkspace($workspace))->toBeTrue()
        ->and($member->canManageWorkspace($workspace))->toBeFalse()
        ->and($outsider->canManageWorkspace($workspace))->toBeFalse();
});

test('a workspace lead has the full capability set, a member has none', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $lead = User::factory()->student()->create();
    membership($lead, $workspace, [WorkspaceRole::WorkspaceLead]);

    $member = User::factory()->student()->create();
    membership($member, $workspace, [WorkspaceRole::Member]);

    expect($lead->workspaceCapabilitiesFor($workspace))->toHaveCount(count(WorkspaceCapability::cases()))
        ->and($member->workspaceCapabilitiesFor($workspace))->toHaveCount(0);
});

test('homeUrl routes members to my tasks and managers to the dashboard', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $lead = User::factory()->student()->create();
    membership($lead, $workspace, [WorkspaceRole::WorkspaceLead]);
    expect($lead->homeUrl())->toBe(route('dashboard', absolute: false));

    $student = User::factory()->student()->create();
    expect($student->homeUrl())->toBe(route('my-tasks', absolute: false));

    $staff = User::factory()->universityStaff()->create();
    expect($staff->homeUrl())->toBe(route('dashboard', absolute: false));
});
