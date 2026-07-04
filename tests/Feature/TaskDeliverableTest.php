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
use App\Notifications\TaskChangesRequestedNotification;
use App\Notifications\TaskDeliverableApprovedNotification;
use App\Notifications\TaskSubmittedForReviewNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function deliveryProjectLeadAndCommittee(): array
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

function deliveryProjectMember(Club $club, Committee $committee, array $roles = [CommitteeRole::Member]): User
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

test('an assignee can submit a deliverable with a file, link, and notes', function () {
    Storage::fake('public');
    Notification::fake();

    [$lead, $club, $committee] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'in_progress',
    ]);

    $this->actingAs($member)
        ->post(route('committees.tasks.deliverable', [$club, $committee, $task]), [
            'deliverable_file' => UploadedFile::fake()->create('demo.pdf', 200, 'application/pdf'),
            'deliverable_url' => 'https://figma.com/file/demo',
            'deliverable_notes' => 'Uploaded the draft and linked the design review.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('review')
        ->and($task->deliverable_url)->toBe('https://figma.com/file/demo')
        ->and($task->deliverable_notes)->toContain('draft')
        ->and($task->submitted_for_review_at)->not->toBeNull()
        ->and($task->getFirstMedia(Task::DELIVERABLE_COLLECTION))->not->toBeNull();

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.deliverable_submitted')->exists())
        ->toBeTrue();

    Notification::assertSentTo($lead, TaskSubmittedForReviewNotification::class);
});

test('a project lead can approve a submitted deliverable', function () {
    Notification::fake();

    [$lead, $club, $committee] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'review',
        'submitted_for_review_at' => now()->subHour(),
    ]);

    $this->actingAs($lead)
        ->post(route('committees.tasks.approve', [$club, $committee, $task]), [
            'review_notes' => 'Looks good. Shipping it.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('done')
        ->and($task->reviewed_by)->toBe($lead->id)
        ->and($task->completed_at)->not->toBeNull()
        ->and($task->review_notes)->toContain('Looks good');

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.deliverable_approved')->exists())
        ->toBeTrue();

    Notification::assertSentTo($member, TaskDeliverableApprovedNotification::class);
});

test('a project lead can request changes on a submitted deliverable', function () {
    Notification::fake();

    [$lead, $club, $committee] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'review',
        'submitted_for_review_at' => now()->subHour(),
    ]);

    $this->actingAs($lead)
        ->post(route('committees.tasks.request-changes', [$club, $committee, $task]), [
            'review_notes' => 'Please tighten the copy and re-upload the PDF.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('in_progress')
        ->and($task->reviewed_by)->toBe($lead->id)
        ->and($task->completed_at)->toBeNull()
        ->and($task->review_notes)->toContain('tighten');

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.changes_requested')->exists())
        ->toBeTrue();

    Notification::assertSentTo($member, TaskChangesRequestedNotification::class);
});

test('non-managers cannot approve deliverables', function () {
    [$lead, $club, $committee] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($club, $committee);
    $otherMember = deliveryProjectMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'review',
    ]);

    $this->actingAs($otherMember)
        ->post(route('committees.tasks.approve', [$club, $committee, $task]))
        ->assertForbidden();
});
