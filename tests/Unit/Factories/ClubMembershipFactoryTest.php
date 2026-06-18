<?php

use App\Enums\ClubRole;
use App\Models\ClubMembership;

test('club membership factory creates approved member by default', function () {
    $membership = ClubMembership::factory()->create();

    expect($membership->status)->toBe('approved')
        ->and($membership->hasClubRole(ClubRole::Member))->toBeTrue()
        ->and($membership->joined_at)->not->toBeNull()
        ->and($membership->user_id)->not->toBeNull()
        ->and($membership->club_id)->not->toBeNull();
});

test('pending club membership factory state clears joined_at', function () {
    $membership = ClubMembership::factory()->pending()->create();

    expect($membership->status)->toBe('pending')
        ->and($membership->joined_at)->toBeNull()
        ->and($membership->reviewed_at)->toBeNull();
});

test('supervisor club membership factory state grants the club lead role', function () {
    $membership = ClubMembership::factory()->supervisor()->create();

    expect($membership->hasClubRole(ClubRole::ClubLead))->toBeTrue();
});

test('rejected club membership factory state stores reason', function () {
    $membership = ClubMembership::factory()->rejected('غير مؤهل')->create();

    expect($membership->status)->toBe('rejected')
        ->and($membership->rejection_reason)->toBe('غير مؤهل')
        ->and($membership->joined_at)->toBeNull();
});
