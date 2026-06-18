<?php

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;

/**
 * Attach an approved membership holding $roles, returning the club.
 *
 * @param  array<int, ClubRole>  $roles
 */
function attachRoles(User $user, array $roles): Club
{
    $club = Club::factory()->create(['status' => 'active']);
    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles($roles);

    return $club;
}

test('a user managing one club gets a single managed_clubs entry in shared props', function () {
    $user = User::factory()->student()->create();
    $club = attachRoles($user, [ClubRole::ClubLead]);

    $this->actingAs($user)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.managed_clubs', 1)
            ->where('auth.user.is_club_supervisor', true)
        );
});

test('a user managing two clubs gets two managed_clubs entries in shared props', function () {
    $user = User::factory()->student()->create();
    $club = attachRoles($user, [ClubRole::ClubLead]);
    attachRoles($user, [ClubRole::EventsManager]);

    $this->actingAs($user)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.managed_clubs', 2)
        );
});

test('a plain member exposes no managed clubs', function () {
    $user = User::factory()->student()->create();
    attachRoles($user, [ClubRole::Member]);

    $this->actingAs($user)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.managed_clubs', 0)
            ->where('auth.user.is_club_supervisor', false)
        );
});
