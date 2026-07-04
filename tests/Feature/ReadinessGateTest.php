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
use Illuminate\Support\Facades\Mail;

function readinessLeadAndProject(): array
{
    $club = Club::factory()->create(['status' => 'active', 'name' => 'Validation Workspace']);
    $committee = Committee::factory()->create([
        'club_id' => $club->id,
        'name' => 'Validation Project',
    ]);
    $lead = User::factory()->student()->create(['name' => 'Project Lead']);

    $clubMembership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $clubMembership->syncClubRoles([ClubRole::ClubLead]);

    $committeeMembership = CommitteeMembership::factory()->create([
        'user_id' => $lead->id,
        'committee_id' => $committee->id,
    ]);
    $committeeMembership->syncCommitteeRoles([CommitteeRole::CommitteeLead, CommitteeRole::Member]);

    return [$lead, $club, $committee];
}

function readinessMember(Club $club, Committee $committee, string $name = 'Project Member'): User
{
    $user = User::factory()->student()->create(['name' => $name]);

    ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
    ]);

    $membership = CommitteeMembership::factory()->create([
        'user_id' => $user->id,
        'committee_id' => $committee->id,
    ]);
    $membership->syncCommitteeRoles([CommitteeRole::Member]);

    return $user;
}

test('the core teamhub workflow stays consistent from task assignment to approval', function () {
    Mail::fake();

    [$lead, $club, $committee] = readinessLeadAndProject();
    $member = readinessMember($club, $committee);

    $this->actingAs($lead)
        ->get(route('committees.manage', [$club, $committee]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Manage')
            ->where('committee.id', $committee->id)
        );

    $this->actingAs($lead)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'Ship the validation phase',
            'description' => 'Prove the core workflow before Phase 6.',
            'assigned_to' => $member->id,
            'priority' => 'high',
            'status' => 'todo',
            'due_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect();

    $task = Task::query()->where('committee_id', $committee->id)->latest()->firstOrFail();

    $this->actingAs($member)
        ->get(route('committees.tasks.show', [$club, $committee, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Show')
            ->where('task.id', $task->id)
            ->where('canComment', true)
            ->has('comments', 0)
            ->has('activities', 2)
        );

    $this->actingAs($member)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->where('auth.user.unread_notifications_count', 1)
            ->has('unreadNotifications', 1)
        );

    $this->actingAs($member)
        ->patch(route('committees.tasks.update', [$club, $committee, $task]), [
            'status' => 'in_progress',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $this->actingAs($member)
        ->post(route('committees.tasks.comments.store', [$club, $committee, $task]), [
            'body' => 'First draft is ready for review.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $this->actingAs($member)
        ->post(route('committees.tasks.deliverable', [$club, $committee, $task]), [
            'deliverable_url' => 'https://example.com/validation-draft',
            'deliverable_notes' => 'Draft uploaded for review.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $this->actingAs($lead)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->has('unreadNotifications', 1)
        );

    $this->actingAs($lead)
        ->post(route('committees.tasks.comments.store', [$club, $committee, $task]), [
            'body' => 'Reviewing now. Thanks for the quick turnaround.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $this->actingAs($lead)
        ->post(route('committees.tasks.approve', [$club, $committee, $task]), [
            'review_notes' => 'Approved for release.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('done')
        ->and(
            TaskActivity::query()
                ->where('task_id', $task->id)
                ->orderBy('id')
                ->pluck('type')
                ->map(fn ($type) => $type->value)
                ->all()
        )->toBe([
            'task.created',
            'task.assigned',
            'task.status_changed',
            'comment.added',
            'task.deliverable_submitted',
            'comment.added',
            'task.deliverable_approved',
        ]);

    $this->actingAs($member)
        ->get(route('committees.tasks.show', [$club, $committee, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Show')
            ->where('task.status', 'done')
            ->has('comments', 2)
            ->has('activities', 7)
        );

    $this->actingAs($member)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->where('auth.user.unread_notifications_count', 2)
            ->has('unreadNotifications', 2)
        );
});

test('member work surfaces stay aligned with the approved workflow outcome', function () {
    Mail::fake();

    [$lead, $club, $committee] = readinessLeadAndProject();
    $member = readinessMember($club, $committee, 'Dashboard Member');

    $this->actingAs($lead)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'Keep my work aligned',
            'assigned_to' => $member->id,
            'priority' => 'medium',
            'status' => 'todo',
            'due_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect();

    $task = Task::query()->where('committee_id', $committee->id)->latest()->firstOrFail();

    $this->actingAs($member)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('summary.open_count', 1)
        );

    $this->actingAs($member)
        ->post(route('committees.tasks.deliverable', [$club, $committee, $task]), [
            'deliverable_url' => 'https://example.com/final',
            'deliverable_notes' => 'Ready.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $this->actingAs($lead)
        ->post(route('committees.tasks.approve', [$club, $committee, $task]), [
            'review_notes' => 'Done.',
        ])
        ->assertRedirect(route('committees.tasks.show', [$club, $committee, $task]));

    $this->actingAs($member)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('summary.open_count', 0)
        );
});
