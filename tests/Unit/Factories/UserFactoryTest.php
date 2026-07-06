<?php

use App\Enums\UserRole;
use App\Models\User;

test('student factory assigns the student tier', function () {
    $user = User::factory()->student()->create();

    expect($user->role)->toBe(UserRole::Member)
        ->and($user->isMember())->toBeTrue();
});

test('club supervisor factory is globally a student', function () {
    // Club supervision is a club-scoped relationship, not a global tier.
    $user = User::factory()->workspaceSupervisor()->create();

    expect($user->role)->toBe(UserRole::Member)
        ->and($user->isMember())->toBeTrue();
});

test('university staff factory assigns the university-staff tier', function () {
    $user = User::factory()->universityStaff()->create();

    expect($user->role)->toBe(UserRole::Admin)
        ->and($user->isAdmin())->toBeTrue();
});
