<?php

use App\Enums\ProjectCapability;
use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, ProjectRole>  $roles
 */
function committeeMembership(User $user, Project $project, array $roles): ProjectMembership
{
    $membership = ProjectMembership::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'status' => 'approved',
    ]);
    $membership->syncProjectRoles($roles);

    return $membership;
}

test('a project lead has the full capability set, a member has none', function () {
    $project = Project::factory()->create();

    $lead = User::factory()->student()->create();
    committeeMembership($lead, $project, [ProjectRole::ProjectLead]);

    $member = User::factory()->student()->create();
    committeeMembership($member, $project, [ProjectRole::Member]);

    expect($lead->projectCapabilitiesFor($project))->toHaveCount(count(ProjectCapability::cases()))
        ->and($member->projectCapabilitiesFor($project))->toHaveCount(0);
});

test('admins and parent-workspace leads inherit every project capability', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $staff = User::factory()->universityStaff()->create();

    $workspaceLead = User::factory()->student()->create();
    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $workspaceLead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    expect($staff->canManageProject($project))->toBeTrue()
        ->and($workspaceLead->canManageProject($project))->toBeTrue()
        ->and($workspaceLead->projectCapabilitiesFor($project))->toHaveCount(count(ProjectCapability::cases()));
});

test('a user with no membership cannot manage a project', function () {
    $project = Project::factory()->create();
    $outsider = User::factory()->student()->create();

    expect($outsider->canManageProject($project))->toBeFalse()
        ->and($outsider->projectCapabilitiesFor($project))->toHaveCount(0);
});

test('managedProjects returns only projects where the user holds a manager role', function () {
    $user = User::factory()->student()->create();
    $led = Project::factory()->create();
    $plain = Project::factory()->create();

    committeeMembership($user, $led, [ProjectRole::ProjectLead]);
    committeeMembership($user, $plain, [ProjectRole::Member]);

    $managed = $user->managedProjects();

    expect($managed)->toHaveCount(1)
        ->and($managed->first()->id)->toBe($led->id);
});

test('project capability gates resolve through the user methods', function () {
    $project = Project::factory()->create();

    $membershipManager = User::factory()->student()->create();
    committeeMembership($membershipManager, $project, [ProjectRole::MembershipManager]);

    expect($membershipManager->can(ProjectCapability::ManageMembers->value, $project))->toBeTrue()
        ->and($membershipManager->can(ProjectCapability::ManageUpdates->value, $project))->toBeFalse();
});
