<?php

use App\Models\Committee;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('task belongs to a committee, creator, assignee, and reviewer', function () {
    $task = Task::factory()->create();

    expect($task->committee())->toBeInstanceOf(BelongsTo::class)
        ->and($task->creator())->toBeInstanceOf(BelongsTo::class)
        ->and($task->assignee())->toBeInstanceOf(BelongsTo::class)
        ->and($task->reviewer())->toBeInstanceOf(BelongsTo::class);
});

test('task scopes filter by committee and assignee', function () {
    $committee = Committee::factory()->create();
    $otherCommittee = Committee::factory()->create();
    $user = User::factory()->student()->create();
    $otherUser = User::factory()->student()->create();

    $matching = Task::factory()->create([
        'committee_id' => $committee->id,
        'assigned_to' => $user->id,
    ]);

    Task::factory()->create([
        'committee_id' => $otherCommittee->id,
        'assigned_to' => $user->id,
    ]);

    Task::factory()->create([
        'committee_id' => $committee->id,
        'assigned_to' => $otherUser->id,
    ]);

    expect(Task::query()->forCommittee($committee)->count())->toBe(2)
        ->and(Task::query()->assignedTo($user)->count())->toBe(2)
        ->and(Task::query()->forCommittee($committee)->assignedTo($user)->first()?->id)->toBe($matching->id);
});

test('task personal scopes split assigned work into overdue, due today, upcoming, and no due date', function () {
    $user = User::factory()->student()->create();

    Task::factory()->create([
        'assigned_to' => $user->id,
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);
    Task::factory()->create([
        'assigned_to' => $user->id,
        'status' => 'in_progress',
        'due_at' => now()->addHours(2),
    ]);
    Task::factory()->create([
        'assigned_to' => $user->id,
        'status' => 'review',
        'due_at' => now()->addDays(2),
    ]);
    Task::factory()->create([
        'assigned_to' => $user->id,
        'status' => 'todo',
        'due_at' => null,
    ]);
    Task::factory()->create([
        'assigned_to' => $user->id,
        'status' => 'done',
        'due_at' => now()->addDay(),
    ]);

    expect(Task::query()->assignedTo($user)->overdue()->count())->toBe(1)
        ->and(Task::query()->assignedTo($user)->dueToday()->count())->toBe(1)
        ->and(Task::query()->assignedTo($user)->upcoming()->count())->toBe(1)
        ->and(Task::query()->assignedTo($user)->withoutDueDate()->count())->toBe(1)
        ->and(Task::query()->assignedTo($user)->incomplete()->count())->toBe(4);
});

test('submitDeliverable moves the task into review', function () {
    $actor = User::factory()->student()->create();
    $task = Task::factory()->create([
        'status' => 'in_progress',
    ]);

    $task->submitDeliverable($actor, 'https://drive.google.com/demo', 'Uploaded the current draft.');

    expect($task->fresh()->status->value)->toBe('review')
        ->and($task->fresh()->deliverable_url)->toBe('https://drive.google.com/demo')
        ->and($task->fresh()->submitted_for_review_at)->not->toBeNull();
});

test('approve and requestChanges apply the expected review state', function () {
    $reviewer = User::factory()->student()->create();
    $task = Task::factory()->create([
        'status' => 'review',
    ]);

    $task->approve($reviewer, 'Approved');

    expect($task->fresh()->status->value)->toBe('done')
        ->and($task->fresh()->completed_at)->not->toBeNull()
        ->and($task->fresh()->review_notes)->toBe('Approved');

    $task->requestChanges($reviewer, 'Fix the spacing');

    expect($task->fresh()->status->value)->toBe('in_progress')
        ->and($task->fresh()->completed_at)->toBeNull()
        ->and($task->fresh()->review_notes)->toBe('Fix the spacing');
});
