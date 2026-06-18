<?php

use App\Enums\EventStatus;
use App\Models\Club;
use App\Models\Event;

test('event factory defaults to active status with club', function () {
    $event = Event::factory()->make();

    expect($event->status)->toBe(EventStatus::Active)
        ->and($event->club_id)->not->toBeNull();
});

test('upcoming factory state sets future start date', function () {
    $event = Event::factory()->upcoming()->create();

    expect($event->starts_at->isFuture())->toBeTrue();
});

test('past factory state sets past start date', function () {
    $event = Event::factory()->past()->create();

    expect($event->starts_at->isPast())->toBeTrue();
});

test('event factory can belong to a specific club', function () {
    $club = Club::factory()->create();
    $event = Event::factory()->for($club)->create();

    expect($event->club_id)->toBe($club->id)
        ->and($event->club->id)->toBe($club->id);
});
