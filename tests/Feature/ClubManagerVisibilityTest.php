<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use App\Notifications\NewPostNotification;
use App\Services\ClubSupervisorReportService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('a manager is excluded from the members report', function () {
    $club = Club::factory()->create();

    // A regular member.
    $member = User::factory()->create();
    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);

    // A manager promoted via the role pivot.
    $manager = User::factory()->create();
    $managerMembership = ClubMembership::factory()->approved()->create([
        'user_id' => $manager->id,
        'club_id' => $club->id,
    ]);
    $managerMembership->assignClubRole(ClubRole::ClubLead);

    $members = app(ClubSupervisorReportService::class)->membersForClub($club);
    $emails = collect($members)->pluck('email');

    expect($emails)->toContain($member->email)
        ->and($emails)->not->toContain($manager->email);
});

test('committee managers are not notified of new project updates', function () {
    Notification::fake();

    $club = Club::factory()->create();
    $committee = Committee::factory()->for($club)->create();

    $lead = User::factory()->create();
    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);

    $member = User::factory()->create();
    CommitteeMembership::factory()->create([
        'user_id' => $member->id,
        'committee_id' => $committee->id,
    ]);

    $manager = User::factory()->create();
    $managerMembership = CommitteeMembership::factory()->create([
        'user_id' => $manager->id,
        'committee_id' => $committee->id,
    ]);
    $managerMembership->assignCommitteeRole(CommitteeRole::ContentManager);

    $this->actingAs($lead)
        ->post(route('committees.news.store', [$club, $committee]), [
            'title' => 'مرحبا',
            'body' => 'نص التحديث هنا للمشروع',
        ])
        ->assertRedirect();

    Notification::assertSentTo($member, NewPostNotification::class);
    Notification::assertNotSentTo($manager, NewPostNotification::class);
    Notification::assertNotSentTo($lead, NewPostNotification::class);
});

test('managedClub returns a fully hydrated club for theme and logo', function () {
    Storage::fake('public');

    $supervisor = User::factory()->create();
    $club = Club::factory()->withTheme('#123456')->withLogo()->create();
    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $managed = $supervisor->managedClub();

    expect($managed)->not->toBeNull()
        ->and($managed->theme)->toBe('#123456')
        ->and($managed->logo_url)->not->toBeNull();
});
