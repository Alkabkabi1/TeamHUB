<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\EventReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// ─── Send Event Reminders Command ─────────────────────────────────────────────

test('reminder is sent to registered attendees of events starting within 24h', function () {
    Notification::fake();

    $user = User::factory()->student()->create();

    // Event starting in ~12h — within the 24h window
    $event = Event::factory()->create([
        'status' => 'active',
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(14),
    ]);

    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertSentTo($user, EventReminderNotification::class);
});

test('a reminder is only sent once even if the command runs again', function () {
    Notification::fake();

    $user = User::factory()->student()->create();

    $event = Event::factory()->create([
        'status' => 'active',
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(14),
    ]);

    $attendance = EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();
    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertSentToTimes($user, EventReminderNotification::class, 1);
    expect($attendance->fresh()->reminder_sent_at)->not->toBeNull();
});

test('approved attendees also receive reminders', function () {
    Notification::fake();

    $user = User::factory()->student()->create();

    $event = Event::factory()->create([
        'status' => 'active',
        'starts_at' => now()->addHours(6),
        'ends_at' => now()->addHours(8),
    ]);

    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'approved',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertSentTo($user, EventReminderNotification::class);
});

test('attendees of far-future events do not receive reminders', function () {
    Notification::fake();

    $user = User::factory()->student()->create();

    // Event starting in 3 days — outside the 24h window
    $event = Event::factory()->create([
        'status' => 'active',
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(2),
    ]);

    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertNotSentTo($user, EventReminderNotification::class);
});

test('attendees of past events do not receive reminders', function () {
    Notification::fake();

    $user = User::factory()->student()->create();

    $event = Event::factory()->past()->create(['status' => 'active']);

    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertNotSentTo($user, EventReminderNotification::class);
});

test('attendees of cancelled events do not receive reminders', function () {
    Notification::fake();

    $user = User::factory()->student()->create();

    $event = Event::factory()->create([
        'status' => 'cancelled',
        'starts_at' => now()->addHours(6),
        'ends_at' => now()->addHours(8),
    ]);

    EventAttendance::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Notification::assertNotSentTo($user, EventReminderNotification::class);
});
