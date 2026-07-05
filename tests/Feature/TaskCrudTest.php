<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

function projectLeadAndCommittee(): array
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

function approvedProjectMember(Workspace $workspace, Project $project, array $roles = [ProjectRole::Member]): User
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

test('tasks table includes the expected core columns', function () {
    expect(Schema::hasColumns('tasks', [
        'project_id',
        'created_by',
        'assigned_to',
        'title',
        'status',
        'priority',
        'due_at',
        'deliverable_url',
        'deliverable_notes',
        'submitted_for_review_at',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
        'review_notes',
        'deleted_at',
    ]))->toBeTrue();
});

test('approved project members can view the project task list', function () {
    [$lead, $workspace, $project] = projectLeadAndCommittee();
    $member = approvedProjectMember($workspace, $project);

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Wire the authenticated task list',
    ]);

    $this->actingAs($member)
        ->get(route('projects.tasks.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Index')
            ->where('committee.id', $project->id)
            ->has('tasks', 1)
        );
});

test('outsiders cannot view project tasks', function () {
    [, $workspace, $project] = projectLeadAndCommittee();
    $outsider = User::factory()->student()->create();

    $this->actingAs($outsider)
        ->get(route('projects.tasks.index', [$workspace, $project]))
        ->assertForbidden();
});

test('a project lead can create and delete a task', function () {
    [$lead, $workspace, $project] = projectLeadAndCommittee();
    $member = approvedProjectMember($workspace, $project);

    $this->actingAs($lead)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Create the first TeamHUB task',
            'description' => 'Hook up the real task domain',
            'assigned_to' => $member->id,
            'priority' => 'high',
            'status' => 'todo',
            'due_at' => now()->addDays(2)->toDateTimeString(),
        ])
        ->assertRedirect();

    $task = Task::query()->where('project_id', $project->id)->firstOrFail();

    expect($task->title)->toBe('Create the first TeamHUB task')
        ->and($task->assigned_to)->toBe($member->id);

    $this->actingAs($lead)
        ->delete(route('projects.tasks.destroy', [$workspace, $project, $task]))
        ->assertRedirect(route('projects.tasks.index', [$workspace, $project]));

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('a project member cannot create tasks', function () {
    [$lead, $workspace, $project] = projectLeadAndCommittee();
    $member = approvedProjectMember($workspace, $project);

    $this->actingAs($member)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Unauthorized task',
        ])
        ->assertForbidden();
});

test('an assignee can update their own task progress', function () {
    [$lead, $workspace, $project] = projectLeadAndCommittee();
    $member = approvedProjectMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'todo',
    ]);

    $this->actingAs($member)
        ->patch(route('projects.tasks.update', [$workspace, $project, $task]), [
            'status' => 'in_progress',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    expect($task->fresh()->status->value)->toBe('in_progress');
});

test('creating and reassigning a task logs activity and sends assignment notifications', function () {
    Notification::fake();

    [$lead, $workspace, $project] = projectLeadAndCommittee();
    $firstAssignee = approvedProjectMember($workspace, $project);
    $secondAssignee = approvedProjectMember($workspace, $project);

    $this->actingAs($lead)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Coordinate the phase five launch',
            'assigned_to' => $firstAssignee->id,
            'priority' => 'medium',
            'status' => 'todo',
        ])
        ->assertRedirect();

    $task = Task::query()->where('project_id', $project->id)->latest()->firstOrFail();

    expect(
        TaskActivity::query()
            ->where('task_id', $task->id)
            ->orderBy('id')
            ->pluck('type')
            ->map(fn ($type) => $type->value)
            ->all()
    )->toBe(['task.created', 'task.assigned']);

    Notification::assertSentTo($firstAssignee, TaskAssignedNotification::class);

    $this->actingAs($lead)
        ->patch(route('projects.tasks.update', [$workspace, $project, $task]), [
            'assigned_to' => $secondAssignee->id,
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    expect(
        TaskActivity::query()
            ->where('task_id', $task->id)
            ->where('type', 'task.assigned')
            ->count()
    )->toBe(2);

    Notification::assertSentTo($secondAssignee, TaskAssignedNotification::class);
});
