<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function notificationLeadAndCommittee(): array
{
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $lead = User::factory()->student()->create();

    $clubMembership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $clubMembership->syncClubRoles([ClubRole::ClubLead]);

    return [$lead, $club, $committee];
}

function notificationMember(Club $club, Committee $committee): User
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
    $membership->syncCommitteeRoles([CommitteeRole::Member]);

    return $user;
}

test('members can view unread task notifications and mark them as read', function () {
    Mail::fake();

    [$lead, $club, $committee] = notificationLeadAndCommittee();
    $member = notificationMember($club, $committee);

    $this->actingAs($lead)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'Ship the notification center',
            'assigned_to' => $member->id,
            'priority' => 'high',
            'status' => 'todo',
        ])
        ->assertRedirect();

    $notification = $member->notifications()->latest()->firstOrFail();

    $this->actingAs($member)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->where('auth.user.unread_notifications_count', 1)
            ->has('unreadNotifications', 1)
        );

    $this->actingAs($member)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($member->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($member->notifications()->firstWhere('id', $notification->id)?->read_at)->not->toBeNull();
});

test('members can mark all notifications as read', function () {
    Mail::fake();

    [$lead, $club, $committee] = notificationLeadAndCommittee();
    $member = notificationMember($club, $committee);

    $this->actingAs($lead)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'First unread task',
            'assigned_to' => $member->id,
            'priority' => 'medium',
            'status' => 'todo',
        ])
        ->assertRedirect();

    $this->actingAs($lead)
        ->post(route('committees.tasks.store', [$club, $committee]), [
            'title' => 'Second unread task',
            'assigned_to' => $member->id,
            'priority' => 'medium',
            'status' => 'todo',
        ])
        ->assertRedirect();

    expect($member->fresh()->unreadNotifications()->count())->toBe(2);

    $this->actingAs($member)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($member->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($member->notifications()->whereNull('read_at')->count())->toBe(0);
});
