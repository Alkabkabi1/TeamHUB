<?php

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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

function projectLeadAndCommittee(): array
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

function approvedProjectMember(Club $club, Committee $committee, array $roles = [CommitteeRole::Member]): User
{
    $user = User::factory()->student()->create();

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

test('tasks table includes the expected core columns', function () {
    expect(Schema::hasColumns('tasks', [
        'committee_id',
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
    [$lead, $club, $committee] = projectLeadAndCommittee();
    $member = approvedProjectMember($club, $committee);

    Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'Wire the authenticated task list',
    ]);

    $this->actingAs($member)
        ->get(route('committees.tasks.index', [$club, $committee]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Index')
            ->where('committee.id', $committee->id)
            ->has('tasks', 1)
        );
});

test('outsiders cannot view project tasks', function () {
    [, $club, $committee] = projectLeadAndCommittee();
    $outsider = User::factory()->student()->create();

    $this->actingAs($outsider)
        ->get(route('committees.tasks.index', [$club, $committee]))
        ->assertForbidden();
});

test('a project lead can create and delete a task', function () {
    [$lead, $club, $committee] = projectLeadAndCommittee();
    $member = approvedProjectMember($club, $committee);

    $this->actingAs($lead)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'Create the first TeamHUB task',
            'description' => 'Hook up the real task domain',
            'assigned_to' => $member->id,
            'priority' => 'high',
            'status' => 'todo',
            'due_at' => now()->addDays(2)->toDateTimeString(),
        ])
        ->assertRedirect();

    $task = Task::query()->where('committee_id', $committee->id)->firstOrFail();

    expect($task->title)->toBe('Create the first TeamHUB task')
        ->and($task->assigned_to)->toBe($member->id);

    $this->actingAs($lead)
        ->delete(route('committees.tasks.destroy', [$club, $committee, $task]))
        ->assertRedirect(route('committees.tasks.index', [$club, $committee]));

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('a project member cannot create tasks', function () {
    [$lead, $club, $committee] = projectLeadAndCommittee();
    $member = approvedProjectMember($club, $committee);

    $this->actingAs($member)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'Unauthorized task',
        ])
        ->assertForbidden();
});

test('an assignee can update their own task progress', function () {
    [$lead, $club, $committee] = projectLeadAndCommittee();
    $member = approvedProjectMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'todo',
    ]);

    $this->actingAs($member)
        ->patch(route('committees.tasks.update', [$club, $committee, $task]), [
            'status' => 'in_progress',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    expect($task->fresh()->status->value)->toBe('in_progress');
});

test('creating and reassigning a task logs activity and sends assignment notifications', function () {
    Notification::fake();

    [$lead, $club, $committee] = projectLeadAndCommittee();
    $firstAssignee = approvedProjectMember($club, $committee);
    $secondAssignee = approvedProjectMember($club, $committee);

    $this->actingAs($lead)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'Coordinate the phase five launch',
            'assigned_to' => $firstAssignee->id,
            'priority' => 'medium',
            'status' => 'todo',
        ])
        ->assertRedirect();

    $task = Task::query()->where('committee_id', $committee->id)->latest()->firstOrFail();

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
        ->patch(route('committees.tasks.update', [$club, $committee, $task]), [
            'assigned_to' => $secondAssignee->id,
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    expect(
        TaskActivity::query()
            ->where('task_id', $task->id)
            ->where('type', 'task.assigned')
            ->count()
    )->toBe(2);

    Notification::assertSentTo($secondAssignee, TaskAssignedNotification::class);
});
