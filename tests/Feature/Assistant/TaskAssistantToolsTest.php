<?php

use App\Ai\Tools\AssignTask;
use App\Ai\Tools\CreateTask;
use App\Ai\Tools\FindTasks;
use App\Ai\Tools\GetProjectSummary;
use App\Ai\Tools\ListMyTasks;
use App\Ai\Tools\UpdateTaskDetails;
use App\Ai\Tools\UpdateTaskStatus;
use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
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
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $lead = User::factory()->student()->create();

    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles([ClubRole::ClubLead]);

    return [$lead, $club, $committee];
}

function assistantProjectMember(
    Club $club,
    Committee $committee,
    array $roles = [CommitteeRole::Member],
    ?string $name = null,
): User {
    $user = User::factory()->student()->create(array_filter(['name' => $name]));

    ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
    ]);

    $membership = CommitteeMembership::factory()->create([
        'user_id' => $user->id,
        'committee_id' => $committee->id,
    ]);
    $membership->syncCommitteeRoles($roles);

    return $user;
}

test('list my tasks only returns the authenticated member tasks', function () {
    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($club, $committee);
    $otherMember = assistantProjectMember($club, $committee);

    Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Overdue homepage copy',
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);

    Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Upcoming review notes',
        'status' => 'in_progress',
        'due_at' => now()->addDays(2),
    ]);

    Task::factory()->create([
        'committee_id' => $committee->id,
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
    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($club, $committee);

    Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'title' => 'Landing page polish',
    ]);

    $hiddenClub = Club::factory()->create(['status' => 'active']);
    $hiddenCommittee = Committee::factory()->create(['club_id' => $hiddenClub->id]);

    Task::factory()->create([
        'committee_id' => $hiddenCommittee->id,
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
    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($club, $committee);

    $overdue = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Fix overdue item',
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);
    $overdue->recordCreated($lead);

    $review = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Review blocker item',
        'status' => 'review',
        'submitted_for_review_at' => now()->subHour(),
    ]);
    $review->recordCreated($lead);

    $result = decodeTaskTool((new GetProjectSummary($member))->handle(new Request([
        'project' => $committee->name,
        'workspace' => $club->name,
    ])));

    expect($result['project']['name'])->toBe($committee->name)
        ->and($result['stats']['overdueCount'])->toBe(1)
        ->and($result['stats']['reviewCount'])->toBe(1)
        ->and(collect($result['blockers'])->pluck('title'))
        ->toContain('Fix overdue item', 'Review blocker item')
        ->and($result['recentActivity'])->not->toBeEmpty();
});

test('a non-manager cannot create tasks through the assistant', function () {
    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($club, $committee);

    $result = decodeTaskTool((new CreateTask($member))->handle(new Request([
        'project' => $committee->name,
        'workspace' => $club->name,
        'title' => 'Blocked creation',
    ])));

    expect($result)->toHaveKey('error');
});

test('a manager can create a task through the confirmation flow', function () {
    Notification::fake();

    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($club, $committee, name: 'Ahmed');

    $this->actingAs($lead);
    $tool = new CreateTask($lead);

    $result = decodeTaskTool($tool->handle(new Request([
        'project' => $committee->name,
        'workspace' => $club->name,
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

    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();
    $from = assistantProjectMember($club, $committee, name: 'Maha');
    $to = assistantProjectMember($club, $committee, name: 'Sara');

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $from->id,
        'title' => 'Reassign design QA',
    ]);

    $this->actingAs($lead);
    $tool = new AssignTask($lead);

    $result = decodeTaskTool($tool->handle(new Request([
        'task' => $task->title,
        'project' => $committee->name,
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
    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'title' => 'Old task title',
        'priority' => 'medium',
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($lead);
    $tool = new UpdateTaskDetails($lead);

    $result = decodeTaskTool($tool->handle(new Request([
        'task' => $task->title,
        'project' => $committee->name,
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

    [$lead, $club, $committee] = assistantProjectLeadAndCommittee();
    $member = assistantProjectMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Submitable task',
        'status' => 'in_progress',
    ]);

    $this->actingAs($member);
    $submitTool = new UpdateTaskStatus($member);

    $submit = decodeTaskTool($submitTool->handle(new Request([
        'task' => $task->title,
        'project' => $committee->name,
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
        'project' => $committee->name,
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
