<?php

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
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

test('club managers are not notified of new posts', function () {
    Notification::fake();

    $club = Club::factory()->create();

    $supervisor = User::factory()->create();
    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $member = User::factory()->create();
    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);

    $manager = User::factory()->create();
    $managerMembership = ClubMembership::factory()->approved()->create([
        'user_id' => $manager->id,
        'club_id' => $club->id,
    ]);
    $managerMembership->assignClubRole(ClubRole::ContentManager);

    $this->actingAs($supervisor)
        ->post(route('news.store', $club), ['title' => 'مرحبا', 'body' => 'نص الخبر هنا للنادي'])
        ->assertRedirect();

    Notification::assertSentTo($member, NewPostNotification::class);
    Notification::assertNotSentTo($manager, NewPostNotification::class);
    Notification::assertNotSentTo($supervisor, NewPostNotification::class);
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
