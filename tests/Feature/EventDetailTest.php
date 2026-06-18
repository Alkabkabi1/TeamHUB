<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEventSupervisor(Club $club): User
{
    $supervisor = User::factory()->clubSupervisor()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    return $supervisor;
}

// ─── Public visibility ────────────────────────────────────────────────────────

test('guest can view an active event', function () {
    $event = Event::factory()->upcoming()->create(['status' => 'active']);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('EventDetailPage')
            ->where('event.id', $event->id)
            ->where('event.status', 'active')
            ->where('isRegistered', false)
            ->where('canManage', false)
            ->has('event.club')
        );
});

test('guest cannot view a draft event', function () {
    $event = Event::factory()->upcoming()->create(['status' => 'draft']);

    $this->get(route('events.show', $event))
        ->assertForbidden();
});

test('guest cannot view a cancelled event', function () {
    $event = Event::factory()->upcoming()->create(['status' => 'cancelled']);

    $this->get(route('events.show', $event))
        ->assertForbidden();
});

test('a managing supervisor can view a draft event', function () {
    $club = Club::factory()->create();
    $supervisor = makeEventSupervisor($club);
    $event = Event::factory()->upcoming()->for($club)->create(['status' => 'draft']);

    $this->actingAs($supervisor)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('EventDetailPage')
            ->where('canManage', true)
        );
});

// ─── Registration state ────────────────────────────────────────────────────────

test('registered student sees their registration state and accurate count', function () {
    $student = User::factory()->student()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => 10]);

    EventAttendance::factory()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->actingAs($student)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isRegistered', true)
            ->where('event.registrations_count', 1)
            ->where('event.is_full', false)
            ->where('event.is_open', true)
        );
});

test('a full event reports is_full true', function () {
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => 1]);

    EventAttendance::factory()->create([
        'user_id' => User::factory()->student()->create()->id,
        'event_id' => $event->id,
        'status' => 'approved',
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('event.is_full', true)
            ->where('event.registrations_count', 1)
        );
});

test('a past active event is not open for registration', function () {
    $event = Event::factory()->past()->create(['status' => 'active']);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('event.is_open', false));
});
