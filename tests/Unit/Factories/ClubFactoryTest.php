<?php

use App\Enums\ClubStatus;
use App\Models\Club;

test('club factory defaults to active status with arabic name prefix', function () {
    $club = Club::factory()->make();

    expect($club->status)->toBe(ClubStatus::Active)
        ->and($club->name)->toStartWith('نادي ');
});

test('inactive factory state sets inactive status', function () {
    $club = Club::factory()->inactive()->make();

    expect($club->status)->toBe(ClubStatus::Inactive);
});

test('founding factory state sets founding status', function () {
    $club = Club::factory()->founding()->make();

    expect($club->status)->toBe(ClubStatus::Founding);
});

test('with theme factory state sets theme color', function () {
    $club = Club::factory()->withTheme('#006471')->make();

    expect($club->theme)->toBe('#006471');
});
