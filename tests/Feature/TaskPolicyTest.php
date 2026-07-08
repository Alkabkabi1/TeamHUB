<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function policyProjectLeadAndCommittee(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);

    $membership = ProjectMembership::factory()->create([
        'user_id' => $lead->id,
        'project_id' => $project->id,
    ]);
    $membership->syncProjectRoles([ProjectRole::ProjectLead]);

    return [$lead, $workspace, $project];
}

function policyProjectMember(Workspace $workspace, Project $project, array $roles = [ProjectRole::Member]): User
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
    $membership->syncProjectRoles($roles);

    return $user;
}

test('project leads can create, update, delete, and review tasks', function () {
    [$lead, $workspace, $project] = policyProjectLeadAndCommittee();
    $member = policyProjectMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
    ]);

    expect($lead->can('viewAny', [Task::class, $project]))->toBeTrue()
        ->and($lead->can('create', [Task::class, $project]))->toBeTrue()
        ->and($lead->can('update', $task))->toBeTrue()
        ->and($lead->can('delete', $task))->toBeTrue()
        ->and($lead->can('approveDeliverable', $task))->toBeTrue();
});

test('workspace leads without project membership cannot assign or review tasks', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $workspaceLead = User::factory()->student()->create();

    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $workspaceLead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $workspaceLead->id,
    ]);

    expect($workspaceLead->can('create', [Task::class, $project]))->toBeFalse()
        ->and($workspaceLead->can('approveDeliverable', $task))->toBeFalse();
});

test('platform admins cannot assign or review tasks without project membership', function () {
    $project = Project::factory()->create();
    $admin = User::factory()->universityStaff()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $admin->id,
    ]);

    expect($admin->can('create', [Task::class, $project]))->toBeFalse()
        ->and($admin->can('approveDeliverable', $task))->toBeFalse();
});

test('approved project members can view tasks and update only their own assigned task', function () {
    [$lead, $workspace, $project] = policyProjectLeadAndCommittee();
    $member = policyProjectMember($workspace, $project);
    $otherMember = policyProjectMember($workspace, $project);

    $assignedTask = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
    ]);
    $otherTask = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $otherMember->id,
    ]);

    expect($member->can('viewAny', [Task::class, $project]))->toBeTrue()
        ->and($member->can('view', $assignedTask))->toBeTrue()
        ->and($member->can('update', $assignedTask))->toBeTrue()
        ->and($member->can('submitDeliverable', $assignedTask))->toBeTrue()
        ->and($member->can('update', $otherTask))->toBeFalse()
        ->and($member->can('approveDeliverable', $assignedTask))->toBeFalse();
});

test('outsiders cannot view or manage project tasks', function () {
    [$lead, , $project] = policyProjectLeadAndCommittee();
    $outsider = User::factory()->student()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
    ]);

    expect($outsider->can('viewAny', [Task::class, $project]))->toBeFalse()
        ->and($outsider->can('view', $task))->toBeFalse()
        ->and($outsider->can('update', $task))->toBeFalse()
        ->and($outsider->can('delete', $task))->toBeFalse();
});
