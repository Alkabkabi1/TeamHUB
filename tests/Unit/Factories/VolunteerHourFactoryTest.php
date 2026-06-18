<?php

use App\Models\VolunteerHour;

test('volunteer hour factory creates record linked to past event', function () {
    $record = VolunteerHour::factory()->create();

    expect($record->hours)->toBeGreaterThan(0)
        ->and($record->event->starts_at->isPast())->toBeTrue()
        ->and($record->approved_at)->toBeNull();
});

test('approved volunteer hour factory state sets approver metadata', function () {
    $record = VolunteerHour::factory()->approved()->create();

    expect($record->approved_by)->not->toBeNull()
        ->and($record->approved_at)->not->toBeNull()
        ->and($record->approver)->not->toBeNull();
});
