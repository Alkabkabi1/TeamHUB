<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, CommitteeRole>  $roles
 */
function committeeMembership(User $user, Committee $committee, array $roles): CommitteeMembership
{
    $membership = CommitteeMembership::factory()->create([
        'user_id' => $user->id,
        'committee_id' => $committee->id,
        'status' => 'approved',
    ]);
    $membership->syncCommitteeRoles($roles);

    return $membership;
}

test('a committee lead has the full capability set, a member has none', function () {
    $committee = Committee::factory()->create();

    $lead = User::factory()->student()->create();
    committeeMembership($lead, $committee, [CommitteeRole::CommitteeLead]);

    $member = User::factory()->student()->create();
    committeeMembership($member, $committee, [CommitteeRole::Member]);

    expect($lead->committeeCapabilitiesFor($committee))->toHaveCount(count(CommitteeCapability::cases()))
        ->and($member->committeeCapabilitiesFor($committee))->toHaveCount(0);
});

test('university staff and parent-club leads inherit every committee capability', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $staff = User::factory()->universityStaff()->create();

    $clubLead = User::factory()->student()->create();
    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $clubLead->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles([ClubRole::ClubLead]);

    expect($staff->canManageCommittee($committee))->toBeTrue()
        ->and($clubLead->canManageCommittee($committee))->toBeTrue()
        ->and($clubLead->committeeCapabilitiesFor($committee))->toHaveCount(count(CommitteeCapability::cases()));
});

test('a user with no membership cannot manage a committee', function () {
    $committee = Committee::factory()->create();
    $outsider = User::factory()->student()->create();

    expect($outsider->canManageCommittee($committee))->toBeFalse()
        ->and($outsider->committeeCapabilitiesFor($committee))->toHaveCount(0);
});

test('managedCommittees returns only committees where the user holds a manager role', function () {
    $user = User::factory()->student()->create();
    $led = Committee::factory()->create();
    $plain = Committee::factory()->create();

    committeeMembership($user, $led, [CommitteeRole::CommitteeLead]);
    committeeMembership($user, $plain, [CommitteeRole::Member]);

    $managed = $user->managedCommittees();

    expect($managed)->toHaveCount(1)
        ->and($managed->first()->id)->toBe($led->id);
});

test('committee capability gates resolve through the user methods', function () {
    $committee = Committee::factory()->create();

    $eventsManager = User::factory()->student()->create();
    committeeMembership($eventsManager, $committee, [CommitteeRole::EventsManager]);

    expect($eventsManager->can(CommitteeCapability::ManageEvents->value, $committee))->toBeTrue()
        ->and($eventsManager->can(CommitteeCapability::ManageNews->value, $committee))->toBeFalse();
});
