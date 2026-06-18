<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\VolunteerHour;

test('guest is redirected when visiting the club management dashboard', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $this->get(route('clubs.manage', $club))
        ->assertRedirect(route('login'));
});

test('student cannot access the club management dashboard', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($student)
        ->get(route('clubs.manage', $club))
        ->assertForbidden();
});

test('supervisor dashboard returns club and hours entry props', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['name' => 'نادي الحاسبات', 'status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
        'checked_in_at' => $pastEvent->starts_at,
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('club.id', $club->id)
            ->where('club.name', 'نادي الحاسبات')
            ->has('pastEvents', 1)
            ->has('eligibleAttendees', 1)
            ->has('stats')
            ->has('members')
            ->where('eligibleAttendees.0.userId', $student->id)
            ->where('eligibleAttendees.0.eventId', $pastEvent->id)
        );
});

test('club supervisor can record volunteer hours for checked in attendance', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
        'checked_in_at' => $pastEvent->starts_at,
    ]);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'event_id' => $pastEvent->id,
            'hours' => 4.5,
        ])
        ->assertRedirect(route('clubs.manage', $club));

    $record = VolunteerHour::query()
        ->where('user_id', $student->id)
        ->where('event_id', $pastEvent->id)
        ->first();

    expect($record)->not->toBeNull()
        ->and((float) $record->hours)->toBe(4.5)
        ->and($record->approved_by)->toBe($supervisor->id)
        ->and($record->approved_at)->not->toBeNull();
});

test('duplicate volunteer hours for same user and event update existing record', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
        'checked_in_at' => $pastEvent->starts_at,
    ]);

    VolunteerHour::factory()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
        'hours' => 2,
        'approved_by' => $supervisor->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'event_id' => $pastEvent->id,
            'hours' => 6,
        ])
        ->assertRedirect(route('clubs.manage', $club));

    expect(VolunteerHour::query()->where('user_id', $student->id)->where('event_id', $pastEvent->id)->count())
        ->toBe(1)
        ->and((float) VolunteerHour::query()->where('user_id', $student->id)->where('event_id', $pastEvent->id)->value('hours'))
        ->toBe(6.0);
});

test('club supervisor can record general volunteer hours without an event', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->approved()->create([
        'user_id' => $student->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'hours' => 7,
        ])
        ->assertRedirect(route('clubs.manage', $club));

    $record = VolunteerHour::query()
        ->where('user_id', $student->id)
        ->where('club_id', $club->id)
        ->first();

    expect($record)->not->toBeNull()
        ->and($record->event_id)->toBeNull()
        ->and((float) $record->hours)->toBe(7.0)
        ->and($record->approved_by)->toBe($supervisor->id)
        ->and($record->approved_at)->not->toBeNull();
});

test('general volunteer hours require an approved club membership', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'hours' => 7,
        ])
        ->assertSessionHasErrors('user_id');

    expect(VolunteerHour::query()->where('user_id', $student->id)->count())->toBe(0);
});

test('general volunteer hours count toward the student dashboard totals', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $student = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->approved()->create([
        'user_id' => $student->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'hours' => 3,
        ])
        ->assertRedirect(route('clubs.manage', $club));

    $this->actingAs($student)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StudentDashboard')
            ->where('totalHours', 3)
            ->where('clubs', fn ($clubs) => collect($clubs)->contains(
                fn ($clubRow) => $clubRow['name'] === $club->name && $clubRow['volunteerHours'] == 3
            ))
        );
});

test('supervisor cannot record hours for another clubs event', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $otherClub = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $otherEvent = Event::factory()->past()->for($otherClub)->create(['status' => 'active']);

    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $otherEvent->id,
        'checked_in_at' => $otherEvent->starts_at,
    ]);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'event_id' => $otherEvent->id,
            'hours' => 3,
        ])
        ->assertSessionHasErrors('event_id');
});

test('student cannot record volunteer hours', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    $this->actingAs($student)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'event_id' => $pastEvent->id,
            'hours' => 3,
        ])
        ->assertForbidden();
});

test('volunteer hours require eligible attendance', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'event_id' => $pastEvent->id,
            'hours' => 3,
        ])
        ->assertSessionHasErrors('user_id');
});

test('volunteer hours cannot be recorded for future events', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $futureEvent = Event::factory()->upcoming()->for($club)->create(['status' => 'active']);

    EventAttendance::factory()->approved()->create([
        'user_id' => $student->id,
        'event_id' => $futureEvent->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'event_id' => $futureEvent->id,
            'hours' => 3,
        ])
        ->assertSessionHasErrors('event_id');
});

test('supervisor recorded hours appear on student dashboard', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $student = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->approved()->create([
        'user_id' => $student->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
        'checked_in_at' => $pastEvent->starts_at,
    ]);

    $this->actingAs($student)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StudentDashboard')
            ->where('totalHours', 0)
        );

    $this->actingAs($supervisor)
        ->post(route('clubs.volunteer-hours.store', $club), [
            'user_id' => $student->id,
            'event_id' => $pastEvent->id,
            'hours' => 5,
        ])
        ->assertRedirect(route('clubs.manage', $club));

    $this->actingAs($student)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StudentDashboard')
            ->where('totalHours', 5)
            ->where('stats.totalHours', 5)
            ->where('clubs', fn ($clubs) => collect($clubs)->contains(
                fn ($clubRow) => $clubRow['name'] === $club->name && $clubRow['volunteerHours'] == 5
            ))
        );
});
