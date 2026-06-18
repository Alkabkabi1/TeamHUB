<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── RSVP ────────────────────────────────────────────────────────────────────

test('authenticated user can rsvp to an active upcoming event', function () {
    $user = User::factory()->student()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => null]);

    $this->actingAs($user)
        ->post(route('events.rsvp', $event))
        ->assertRedirect();

    $this->assertDatabaseHas('event_attendances', [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);
});

test('guest cannot rsvp', function () {
    $event = Event::factory()->upcoming()->create(['status' => 'active']);

    $this->post(route('events.rsvp', $event))
        ->assertRedirect(route('login'));
});

test('duplicate rsvp is idempotent', function () {
    $user = User::factory()->student()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => null]);

    // First RSVP
    $this->actingAs($user)->post(route('events.rsvp', $event));

    // Second RSVP — should not create a duplicate
    $this->actingAs($user)->post(route('events.rsvp', $event))->assertRedirect();

    expect(EventAttendance::query()
        ->where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->count()
    )->toBe(1);
});

test('rsvp is blocked when event is at capacity and user is not already registered', function () {
    $user = User::factory()->student()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => 1]);

    // Fill capacity with another user
    $other = User::factory()->student()->create();
    EventAttendance::factory()->create([
        'user_id' => $other->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->actingAs($user)
        ->post(route('events.rsvp', $event))
        ->assertRedirect();

    $this->assertDatabaseMissing('event_attendances', [
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);
});

test('already registered user can rsvp again even when capacity is full (idempotent)', function () {
    $user = User::factory()->student()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => 1]);

    // Register the user
    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    // Attempt RSVP again — should succeed (idempotent)
    $this->actingAs($user)
        ->post(route('events.rsvp', $event))
        ->assertRedirect();

    expect(EventAttendance::query()
        ->where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->count()
    )->toBe(1);
});

test('rsvp is blocked for a non-active event', function () {
    $user = User::factory()->student()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'draft']);

    $this->actingAs($user)
        ->post(route('events.rsvp', $event))
        ->assertRedirect();

    $this->assertDatabaseMissing('event_attendances', [
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);
});

// ─── Cancel RSVP ─────────────────────────────────────────────────────────────

test('user can cancel their rsvp for an upcoming event', function () {
    $user = User::factory()->student()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active']);

    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->actingAs($user)
        ->delete(route('events.rsvp.cancel', $event))
        ->assertRedirect();

    $this->assertDatabaseMissing('event_attendances', [
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);
});

test('user cannot cancel rsvp for a past event', function () {
    $user = User::factory()->student()->create();
    $event = Event::factory()->past()->create(['status' => 'active']);

    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->actingAs($user)
        ->delete(route('events.rsvp.cancel', $event))
        ->assertRedirect();

    // Attendance should still exist
    $this->assertDatabaseHas('event_attendances', [
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);
});

// ─── Role Guard ───────────────────────────────────────────────────────────────

test('club manager can rsvp as a student', function () {
    // Club supervision is a club-scoped role; globally a supervisor is a student
    // and may participate in events like any other student.
    $supervisor = User::factory()->clubSupervisor()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => null]);

    $this->actingAs($supervisor)
        ->post(route('events.rsvp', $event))
        ->assertRedirect();

    $this->assertDatabaseHas('event_attendances', [
        'user_id' => $supervisor->id,
        'event_id' => $event->id,
    ]);
});

test('university staff gets 403 when attempting rsvp', function () {
    $staff = User::factory()->universityStaff()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active', 'capacity' => null]);

    $this->actingAs($staff)
        ->post(route('events.rsvp', $event))
        ->assertForbidden();

    $this->assertDatabaseMissing('event_attendances', [
        'user_id' => $staff->id,
        'event_id' => $event->id,
    ]);
});

test('university staff gets 403 when attempting to cancel rsvp', function () {
    $staff = User::factory()->universityStaff()->create();
    $event = Event::factory()->upcoming()->create(['status' => 'active']);

    $this->actingAs($staff)
        ->delete(route('events.rsvp.cancel', $event))
        ->assertForbidden();
});
