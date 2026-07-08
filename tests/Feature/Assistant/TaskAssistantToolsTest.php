<?php

use App\Ai\Tools\AssignTask;
use App\Ai\Tools\CreateTask;
use App\Ai\Tools\FindTasks;
use App\Ai\Tools\GetProjectSummary;
use App\Ai\Tools\ListMyTasks;
use App\Ai\Tools\UpdateTaskDetails;
use App\Ai\Tools\UpdateTaskStatus;
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
use App\Notifications\TaskDeliverableApprovedNotification;
use App\Notifications\TaskSubmittedForReviewNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Tools\Request;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function decodeTaskTool(string $json): array
{
    return json_decode($json, true);
}

/**
 * @return array{0: User, 1: Club, 2: Committee}
 */
function assistantProjectLeadAndCommittee(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);
    grantProjectLead($lead, $project);

    return [$lead, $workspace, $project];
}

function assistantProjectMember(
    Workspace $workspace,
    Project $project,
    array $roles = [ProjectRole::Member],
    ?string $name = null,
): User {
    $user = User::factory()->student()->create(array_filter(['name' => $name]));

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

test('list my tasks only returns the authenticated member tasks', function () {
    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($workspace, $project);
    $otherMember = assistantProjectMember($workspace, $project);

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Overdue homepage copy',
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Upcoming review notes',
        'status' => 'in_progress',
        'due_at' => now()->addDays(2),
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $otherMember->id,
        'title' => 'Someone else task',
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);

    $result = decodeTaskTool((new ListMyTasks($member))->handle(new Request([
        'bucket' => 'overdue',
    ])));

    expect($result['summary']['openCount'])->toBe(2)
        ->and($result['summary']['overdueCount'])->toBe(1)
        ->and($result['tasks'])->toHaveCount(1)
        ->and($result['tasks'][0]['title'])->toBe('Overdue homepage copy');
});

test('find tasks is scoped to projects the user can view', function () {
    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($workspace, $project);

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'title' => 'Landing page polish',
    ]);

    $hiddenClub = Workspace::factory()->create(['status' => 'active']);
    $hiddenCommittee = Project::factory()->create(['workspace_id' => $hiddenClub->id]);

    Task::factory()->create([
        'project_id' => $hiddenCommittee->id,
        'created_by' => $lead->id,
        'title' => 'Landing page migration',
    ]);

    $result = decodeTaskTool((new FindTasks($member))->handle(new Request([
        'search' => 'Landing page',
    ])));

    expect($result['count'])->toBe(1)
        ->and($result['tasks'][0]['title'])->toBe('Landing page polish');
});

test('project summary includes counts blockers and recent activity', function () {
    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($workspace, $project);

    $overdue = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Fix overdue item',
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);
    $overdue->recordCreated($lead);

    $review = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Review blocker item',
        'status' => 'review',
        'submitted_for_review_at' => now()->subHour(),
    ]);
    $review->recordCreated($lead);

    $result = decodeTaskTool((new GetProjectSummary($member))->handle(new Request([
        'project' => $project->name,
        'workspace' => $workspace->name,
    ])));

    expect($result['project']['name'])->toBe($project->name)
        ->and($result['stats']['overdueCount'])->toBe(1)
        ->and($result['stats']['reviewCount'])->toBe(1)
        ->and(collect($result['blockers'])->pluck('title'))
        ->toContain('Fix overdue item', 'Review blocker item')
        ->and($result['recentActivity'])->not->toBeEmpty();
});

test('a non-manager cannot create tasks through the assistant', function () {
    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($workspace, $project);

    $result = decodeTaskTool((new CreateTask($member))->handle(new Request([
        'project' => $project->name,
        'workspace' => $workspace->name,
        'title' => 'Blocked creation',
    ])));

    expect($result)->toHaveKey('error');
});

test('a manager can create a task through the confirmation flow', function () {
    Notification::fake();

    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($workspace, $project, name: 'Ahmed');

    $this->actingAs($lead);
    $tool = new CreateTask($lead);

    $result = decodeTaskTool($tool->handle(new Request([
        'project' => $project->name,
        'workspace' => $workspace->name,
        'title' => 'Prepare sprint board',
        'assignee' => 'Ahmed',
        'priority' => 'high',
        'due_at' => now()->addDays(3)->toIso8601String(),
    ])));

    expect($result['status'])->toBe('pending_confirmation');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");
    $outcome = $tool->execute($cached['params']);
    $task = Task::query()->where('title', 'Prepare sprint board')->first();

    expect($outcome['success'])->toBeTrue()
        ->and($task)->not->toBeNull()
        ->and($task->assigned_to)->toBe($member->id);

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.created')->exists())
        ->toBeTrue();
    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.assigned')->exists())
        ->toBeTrue();

    Notification::assertSentTo($member, TaskAssignedNotification::class);
});

test('a manager can reassign a task through the confirmation flow', function () {
    Notification::fake();

    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();
    $from = assistantProjectMember($workspace, $project, name: 'Maha');
    $to = assistantProjectMember($workspace, $project, name: 'Sara');

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $from->id,
        'title' => 'Reassign design QA',
    ]);

    $this->actingAs($lead);
    $tool = new AssignTask($lead);

    $result = decodeTaskTool($tool->handle(new Request([
        'task' => $task->title,
        'project' => $project->name,
        'assignee' => 'Sara',
    ])));

    expect($result['status'])->toBe('pending_confirmation');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");
    $outcome = $tool->execute($cached['params']);

    expect($outcome['success'])->toBeTrue()
        ->and($task->fresh()->assigned_to)->toBe($to->id);

    Notification::assertSentTo($to, TaskAssignedNotification::class);
});

test('a manager can update task details through the confirmation flow', function () {
    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'title' => 'Old task title',
        'priority' => 'medium',
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($lead);
    $tool = new UpdateTaskDetails($lead);

    $result = decodeTaskTool($tool->handle(new Request([
        'task' => $task->title,
        'project' => $project->name,
        'title' => 'Updated task title',
        'priority' => 'urgent',
        'clear_due_date' => 'true',
    ])));

    expect($result['status'])->toBe('pending_confirmation');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");
    $outcome = $tool->execute($cached['params']);

    expect($outcome['success'])->toBeTrue()
        ->and($task->fresh()->title)->toBe('Updated task title')
        ->and($task->fresh()->priority->value)->toBe('urgent')
        ->and($task->fresh()->due_at)->toBeNull();
});

test('task status updates preserve review activity and notifications', function () {
    Notification::fake();

    [$lead, $workspace, $project] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Submitable task',
        'status' => 'in_progress',
    ]);

    $this->actingAs($member);
    $submitTool = new UpdateTaskStatus($member);

    $submit = decodeTaskTool($submitTool->handle(new Request([
        'task' => $task->title,
        'project' => $project->name,
        'status' => 'review',
        'deliverable_notes' => 'Ready for review in TeamHUB.',
    ])));

    expect($submit['status'])->toBe('pending_confirmation');

    $submitCached = Cache::get("ai_pending_action:{$submit['action_id']}");
    $submitOutcome = $submitTool->execute($submitCached['params']);

    expect($submitOutcome['success'])->toBeTrue()
        ->and($task->fresh()->status->value)->toBe('review');

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.deliverable_submitted')->exists())
        ->toBeTrue();
    Notification::assertSentTo($lead, TaskSubmittedForReviewNotification::class);

    $this->actingAs($lead);
    $approveTool = new UpdateTaskStatus($lead);

    $approve = decodeTaskTool($approveTool->handle(new Request([
        'task' => $task->title,
        'project' => $project->name,
        'status' => 'done',
        'review_notes' => 'Looks good.',
    ])));

    expect($approve['status'])->toBe('pending_confirmation');

    $approveCached = Cache::get("ai_pending_action:{$approve['action_id']}");
    $approveOutcome = $approveTool->execute($approveCached['params']);

    expect($approveOutcome['success'])->toBeTrue()
        ->and($task->fresh()->status->value)->toBe('done');

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.deliverable_approved')->exists())
        ->toBeTrue();
    Notification::assertSentTo($member, TaskDeliverableApprovedNotification::class);
});
