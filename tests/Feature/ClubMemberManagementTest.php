<?php

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;

/**
 * Create an approved membership in $club holding the given roles.
 *
 * @param  array<int, ClubRole>  $roles
 */
function membershipWithRoles(Club $club, array $roles, ?User $user = null): ClubMembership
{
    $user ??= User::factory()->student()->create();

    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
    ]);

    $membership->syncClubRoles($roles);

    return $membership;
}

test('a club lead can add an existing student as an approved member', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($club, [ClubRole::ClubLead])->user;
    $student = User::factory()->student()->create();

    $this->actingAs($lead)
        ->post(route('clubs.members.store', $club), ['user_id' => $student->id])
        ->assertRedirect();

    $membership = ClubMembership::where('user_id', $student->id)
        ->where('club_id', $club->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->status)->toBe('approved')
        ->and($membership->hasClubRole(ClubRole::Member))->toBeTrue();
});

test('a plain member cannot add members', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $member = membershipWithRoles($club, [ClubRole::Member])->user;
    $student = User::factory()->student()->create();

    $this->actingAs($member)
        ->post(route('clubs.members.store', $club), ['user_id' => $student->id])
        ->assertForbidden();
});

test('a membership manager cannot grant a manager role when adding a member', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $manager = membershipWithRoles($club, [ClubRole::MembershipManager])->user;
    $student = User::factory()->student()->create();

    $this->actingAs($manager)
        ->post(route('clubs.members.store', $club), [
            'user_id' => $student->id,
            'roles' => [ClubRole::EventsManager->value],
        ])
        ->assertForbidden();
});

test('member search returns matching students excluding existing members', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($club, [ClubRole::ClubLead])->user;

    $match = User::factory()->student()->create(['name' => 'Searchable Sara']);
    $existing = membershipWithRoles($club, [ClubRole::Member])->user;
    $existing->update(['name' => 'Searchable Sami']);

    $this->actingAs($lead)
        ->getJson(route('clubs.members.search', ['club' => $club, 'q' => 'Searchable']))
        ->assertOk()
        ->assertJsonFragment(['id' => $match->id])
        ->assertJsonMissing(['id' => $existing->id]);
});

test('updating member roles requires the manage-club capability', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $membershipManager = membershipWithRoles($club, [ClubRole::MembershipManager])->user;
    $target = membershipWithRoles($club, [ClubRole::Member]);

    $this->actingAs($membershipManager)
        ->put(route('clubs.members.roles', ['club' => $club, 'membership' => $target]), [
            'roles' => [ClubRole::EventsManager->value],
        ])
        ->assertForbidden();
});

test('a club lead can promote a member to a manager role', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($club, [ClubRole::ClubLead])->user;
    $target = membershipWithRoles($club, [ClubRole::Member]);

    $this->actingAs($lead)
        ->put(route('clubs.members.roles', ['club' => $club, 'membership' => $target]), [
            'roles' => [ClubRole::EventsManager->value],
        ])
        ->assertRedirect();

    $target->refresh();
    expect($target->hasClubRole(ClubRole::EventsManager))->toBeTrue()
        ->and($target->hasClubRole(ClubRole::Member))->toBeTrue();
});

test('the last club lead cannot be demoted', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $leadMembership = membershipWithRoles($club, [ClubRole::ClubLead]);
    $lead = $leadMembership->user;

    $this->actingAs($lead)
        ->put(route('clubs.members.roles', ['club' => $club, 'membership' => $leadMembership]), [
            'roles' => [],
        ]);

    $leadMembership->refresh();
    expect($leadMembership->hasClubRole(ClubRole::ClubLead))->toBeTrue();
});

test('the last club lead cannot be removed', function () {
    $staff = User::factory()->universityStaff()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $leadMembership = membershipWithRoles($club, [ClubRole::ClubLead]);

    $this->actingAs($staff)
        ->delete(route('clubs.members.destroy', ['club' => $club, 'membership' => $leadMembership]));

    expect(ClubMembership::find($leadMembership->id))->not->toBeNull();
});

test('a club lead can remove a plain member', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($club, [ClubRole::ClubLead])->user;
    $target = membershipWithRoles($club, [ClubRole::Member]);

    $this->actingAs($lead)
        ->delete(route('clubs.members.destroy', ['club' => $club, 'membership' => $target]))
        ->assertRedirect();

    expect(ClubMembership::find($target->id))->toBeNull();
});

test('roles cannot be managed on a membership belonging to another club', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $otherClub = Club::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($club, [ClubRole::ClubLead])->user;
    $foreignMembership = membershipWithRoles($otherClub, [ClubRole::Member]);

    $this->actingAs($lead)
        ->put(route('clubs.members.roles', ['club' => $club, 'membership' => $foreignMembership]), [
            'roles' => [ClubRole::EventsManager->value],
        ])
        ->assertNotFound();
});
