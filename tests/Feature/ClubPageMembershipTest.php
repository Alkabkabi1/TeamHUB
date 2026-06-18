<?php

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\User;

test('isMember is false for guest on club page', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', false)
        );
});

test('isMember is false for user with no membership', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', false)
        );
});

test('isMember is true for approved member', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::create([
        'user_id' => $user->id,
        'club_id' => $club->id,
        'status' => 'approved',
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', true)
        );
});

test('isMember is true for user with pending application', function () {
    $user = User::factory()->create(['email' => 'pending@uqu.edu.sa']);
    $club = Club::factory()->create(['status' => 'active']);

    ClubJoinApplication::factory()->pending()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
        'university_email' => $user->email,
    ]);

    $this->actingAs($user)
        ->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', true)
        );
});

test('is_member flag is false on clubs catalog for non-member', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create(['status' => 'active', 'name' => 'Test Club']);

    $this->actingAs($user)
        ->get(route('clubs'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubsPage')
            ->has('clubs', 1, fn ($c) => $c
                ->where('id', $club->id)
                ->where('is_member', false)
                ->etc()
            )
        );
});

test('is_member flag is true on clubs catalog for approved member', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create(['status' => 'active', 'name' => 'Test Club']);

    ClubMembership::create([
        'user_id' => $user->id,
        'club_id' => $club->id,
        'status' => 'approved',
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('clubs'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubsPage')
            ->has('clubs', 1, fn ($c) => $c
                ->where('id', $club->id)
                ->where('is_member', true)
                ->etc()
            )
        );
});
