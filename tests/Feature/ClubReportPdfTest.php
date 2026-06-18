<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\VolunteerHour;

test('guest is redirected when downloading club reports', function () {
    $club = Club::factory()->create();

    $this->get(route('clubs.reports.members', ['club' => $club]))
        ->assertRedirect(route('login'));
});

test('student cannot download club reports', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();

    $this->actingAs($student)
        ->get(route('clubs.reports.members', ['club' => $club]))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('clubs.reports.volunteer-hours', ['club' => $club]))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('clubs.reports.attendance', ['club' => $club]))
        ->assertForbidden();
});

test('supervisor cannot download reports for another club', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $supervisedClub = Club::factory()->create(['status' => 'active']);
    $otherClub = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $supervisedClub->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.reports.members', ['club' => $otherClub]))
        ->assertForbidden();
});

test('supervisor can download pdf reports for supervised club', function (string $routeName) {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active', 'name' => 'نادي الحاسبات']);
    $member = User::factory()->student()->create(['name' => 'عضو تجريبي']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $member->id,
        'event_id' => $pastEvent->id,
        'checked_in_at' => $pastEvent->starts_at,
    ]);

    VolunteerHour::factory()->create([
        'user_id' => $member->id,
        'event_id' => $pastEvent->id,
        'hours' => 4,
        'approved_by' => $supervisor->id,
        'approved_at' => now(),
    ]);

    $response = $this->actingAs($supervisor)
        ->get(route($routeName, ['club' => $club, 'locale' => 'ar']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
    expect(strlen($response->getContent() ?? ''))->toBeGreaterThan(100);
})->with([
    'members report' => 'clubs.reports.members',
    'volunteer hours report' => 'clubs.reports.volunteer-hours',
    'attendance report' => 'clubs.reports.attendance',
]);

test('supervisor can download english locale report', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.reports.members', ['club' => $club, 'locale' => 'en']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('invalid report locale returns validation error', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.reports.members', ['club' => $club, 'locale' => 'fr']))
        ->assertInvalid(['locale']);
});
