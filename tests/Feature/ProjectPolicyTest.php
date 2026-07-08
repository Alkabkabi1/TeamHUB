<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function projectPolicyWorkspaceLead(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    return [$lead, $workspace, $project];
}

function projectPolicyProjectLead(Workspace $workspace, Project $project): User
{
    $user = User::factory()->student()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $membership = ProjectMembership::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
    ]);
    $membership->syncProjectRoles([ProjectRole::ProjectLead]);

    return $user;
}

function projectPolicyMember(Workspace $workspace, Project $project): User
{
    $user = User::factory()->student()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $membership = ProjectMembership::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
    ]);
    $membership->syncProjectRoles([ProjectRole::Member]);

    return $user;
}

test('any authenticated user can list and view projects', function () {
    [$lead, $workspace, $project] = projectPolicyWorkspaceLead();
    $member = projectPolicyMember($workspace, $project);

    expect($lead->can('viewAny', Project::class))->toBeTrue()
        ->and($lead->can('view', $project))->toBeTrue()
        ->and($member->can('viewAny', Project::class))->toBeTrue()
        ->and($member->can('view', $project))->toBeTrue();
});

test('workspace leads and admins can create projects', function () {
    [$lead, $workspace, $project] = projectPolicyWorkspaceLead();
    $member = projectPolicyMember($workspace, $project);
    $admin = User::factory()->admin()->create();

    expect($lead->can('create', [Project::class, $project]))->toBeTrue()
        ->and($admin->can('create', [Project::class, $project]))->toBeTrue()
        ->and($member->can('create', [Project::class, $project]))->toBeFalse();
});

test('project leads can update projects but plain members cannot', function () {
    [$lead, $workspace, $project] = projectPolicyWorkspaceLead();
    $projectLead = projectPolicyProjectLead($workspace, $project);
    $member = projectPolicyMember($workspace, $project);

    expect($projectLead->can('update', $project))->toBeTrue()
        ->and($lead->can('update', $project))->toBeTrue()
        ->and($member->can('update', $project))->toBeFalse();
});

test('workspace leads and admins can delete projects', function () {
    [$lead, $workspace, $project] = projectPolicyWorkspaceLead();
    $projectLead = projectPolicyProjectLead($workspace, $project);
    $admin = User::factory()->admin()->create();

    expect($lead->can('delete', $project))->toBeTrue()
        ->and($admin->can('delete', $project))->toBeTrue()
        ->and($projectLead->can('delete', $project))->toBeFalse();
});

test('force delete is denied for everyone', function () {
    [$lead, , $project] = projectPolicyWorkspaceLead();
    $admin = User::factory()->admin()->create();

    expect($admin->can('forceDelete', $project))->toBeFalse()
        ->and($lead->can('forceDelete', $project))->toBeFalse();
});
