<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\EventCancelledNotification;
use App\Notifications\EventUpdatedNotification;
use App\Notifications\RsvpConfirmationNotification;
use Illuminate\Support\Facades\Notification;

function eventSupervisor(Club $club): User
{
    $supervisor = User::factory()->clubSupervisor()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    return $supervisor;
}

// ---------------------------------------------------------------------------
// RSVP confirmation (REQ-15)
// ---------------------------------------------------------------------------

test('a fresh rsvp sends the student a confirmation, a repeat does not', function () {
    Notification::fake();

    $student = User::factory()->student()->create();
    $event = Event::factory()->for(Club::factory()->create(['status' => 'active']))
        ->create(['status' => 'active', 'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(2)]);

    $this->actingAs($student)->post(route('events.rsvp', $event))->assertRedirect();
    $this->actingAs($student)->post(route('events.rsvp', $event))->assertRedirect();

    Notification::assertSentToTimes($student, RsvpConfirmationNotification::class, 1);
});

// ---------------------------------------------------------------------------
// Cancellation / reschedule notify registered attendees
// ---------------------------------------------------------------------------

test('cancelling an event notifies registered attendees', function () {
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    $supervisor = eventSupervisor($club);
    $student = User::factory()->student()->create();
    $event = Event::factory()->for($club)->create([
        'status' => 'active',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
    ]);
    EventAttendance::factory()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->actingAs($supervisor)
        ->put(route('events.update', ['club' => $club, 'event' => $event]), [
            'title' => $event->title,
            'starts_at' => $event->starts_at->toDateTimeString(),
            'ends_at' => $event->ends_at->toDateTimeString(),
            'location' => $event->location,
            'status' => 'cancelled',
        ])
        ->assertRedirect();

    Notification::assertSentTo($student, EventCancelledNotification::class);
    Notification::assertNotSentTo($student, EventUpdatedNotification::class);
});

test('rescheduling an event notifies registered attendees', function () {
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    $supervisor = eventSupervisor($club);
    $student = User::factory()->student()->create();
    $event = Event::factory()->for($club)->create([
        'status' => 'active',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
    ]);
    EventAttendance::factory()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->actingAs($supervisor)
        ->put(route('events.update', ['club' => $club, 'event' => $event]), [
            'title' => $event->title,
            'starts_at' => now()->addWeeks(2)->toDateTimeString(),
            'ends_at' => now()->addWeeks(2)->addHours(2)->toDateTimeString(),
            'location' => 'New Hall',
            'status' => 'active',
        ])
        ->assertRedirect();

    Notification::assertSentTo($student, EventUpdatedNotification::class);
    Notification::assertNotSentTo($student, EventCancelledNotification::class);
});

test('editing an event without schedule or status changes notifies no one', function () {
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    $supervisor = eventSupervisor($club);
    $student = User::factory()->student()->create();
    $event = Event::factory()->for($club)->create([
        'status' => 'active',
        'title' => 'Original',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
        'location' => 'Hall A',
    ]);
    EventAttendance::factory()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->actingAs($supervisor)
        ->put(route('events.update', ['club' => $club, 'event' => $event]), [
            'title' => 'Updated title only',
            'starts_at' => $event->starts_at->toDateTimeString(),
            'ends_at' => $event->ends_at->toDateTimeString(),
            'location' => 'Hall A',
            'status' => 'active',
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($student, EventUpdatedNotification::class);
    Notification::assertNotSentTo($student, EventCancelledNotification::class);
});
