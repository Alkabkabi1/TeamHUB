<?php

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, ClubRole>  $roles
 */
function membership(User $user, Club $club, array $roles): ClubMembership
{
    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles($roles);

    return $membership;
}

test('managedClubs returns only clubs where the user holds a manager role', function () {
    $user = User::factory()->student()->create();
    $ledClub = Club::factory()->create(['status' => 'active']);
    $memberClub = Club::factory()->create(['status' => 'active']);

    membership($user, $ledClub, [ClubRole::ClubLead]);
    membership($user, $memberClub, [ClubRole::Member]);

    $managed = $user->managedClubs();

    expect($managed)->toHaveCount(1)
        ->and($managed->first()->id)->toBe($ledClub->id);
});

test('canManageClub is true for managers and university staff, false otherwise', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $lead = User::factory()->student()->create();
    membership($lead, $club, [ClubRole::ClubLead]);

    $member = User::factory()->student()->create();
    membership($member, $club, [ClubRole::Member]);

    $outsider = User::factory()->student()->create();
    $staff = User::factory()->universityStaff()->create();

    expect($lead->canManageClub($club))->toBeTrue()
        ->and($staff->canManageClub($club))->toBeTrue()
        ->and($member->canManageClub($club))->toBeFalse()
        ->and($outsider->canManageClub($club))->toBeFalse();
});

test('a club lead has the full capability set, a member has none', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $lead = User::factory()->student()->create();
    membership($lead, $club, [ClubRole::ClubLead]);

    $member = User::factory()->student()->create();
    membership($member, $club, [ClubRole::Member]);

    expect($lead->clubCapabilitiesFor($club))->toHaveCount(count(ClubCapability::cases()))
        ->and($member->clubCapabilitiesFor($club))->toHaveCount(0);
});

test('homeUrl routes all non-staff users to the team hub dashboard', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $lead = User::factory()->student()->create();
    membership($lead, $club, [ClubRole::ClubLead]);
    expect($lead->homeUrl())->toBe(route('hub.dashboard', absolute: false));

    $student = User::factory()->student()->create();
    expect($student->homeUrl())->toBe(route('hub.dashboard', absolute: false));

    $staff = User::factory()->universityStaff()->create();
    expect($staff->homeUrl())->toBe(route('filament.admin.pages.dashboard', absolute: false));
});
