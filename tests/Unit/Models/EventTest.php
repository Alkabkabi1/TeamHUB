<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('upcoming scope returns only future events', function () {
    Event::factory()->upcoming()->create();
    Event::factory()->past()->create();

    expect(Event::query()->upcoming()->count())->toBe(1);
});

test('past scope returns only started events', function () {
    Event::factory()->upcoming()->create();
    Event::factory()->past()->create();

    expect(Event::query()->past()->count())->toBe(1);
});

test('active scope returns only active events', function () {
    Event::factory()->create(['status' => EventStatus::Active->value]);
    Event::factory()->create(['status' => EventStatus::Draft->value]);
    Event::factory()->create(['status' => EventStatus::Cancelled->value]);

    expect(Event::query()->active()->count())->toBe(1);
});

test('status is cast to the EventStatus enum', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft->value]);

    expect($event->refresh()->status)->toBe(EventStatus::Draft);
});

test('isFull is true only when registered seats reach capacity', function () {
    $event = Event::factory()->upcoming()->create(['capacity' => 2]);

    expect($event->isFull())->toBeFalse();

    foreach (range(1, 2) as $i) {
        EventAttendance::factory()->create([
            'user_id' => User::factory()->student()->create()->id,
            'event_id' => $event->id,
            'status' => 'registered',
        ]);
    }

    expect($event->isFull())->toBeTrue();
});

test('isFull is always false when capacity is unlimited', function () {
    $event = Event::factory()->upcoming()->create(['capacity' => null]);

    EventAttendance::factory()->create([
        'user_id' => User::factory()->student()->create()->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    expect($event->isFull())->toBeFalse();
});

test('isOpenForRegistration requires an active, not-yet-started event', function () {
    expect(Event::factory()->upcoming()->make(['status' => EventStatus::Active->value])->isOpenForRegistration())->toBeTrue()
        ->and(Event::factory()->upcoming()->make(['status' => EventStatus::Draft->value])->isOpenForRegistration())->toBeFalse()
        ->and(Event::factory()->past()->make(['status' => EventStatus::Active->value])->isOpenForRegistration())->toBeFalse();
});
