<?php

use App\Models\EventAttendance;

test('event attendance factory defaults to pending', function () {
    $attendance = EventAttendance::factory()->create();

    expect($attendance->status)->toBe('pending')
        ->and($attendance->checked_in_at)->toBeNull();
});

test('checked in factory state records check in time', function () {
    $attendance = EventAttendance::factory()->checkedIn()->create();

    expect($attendance->status)->toBe('checked_in')
        ->and($attendance->checked_in_at)->not->toBeNull();
});

test('for past event factory state links to a past event', function () {
    $attendance = EventAttendance::factory()->forPastEvent()->create();

    expect($attendance->event->starts_at->isPast())->toBeTrue();
});

test('approved factory state keeps checked_in_at empty', function () {
    $attendance = EventAttendance::factory()->approved()->create();

    expect($attendance->status)->toBe('approved')
        ->and($attendance->checked_in_at)->toBeNull();
});
