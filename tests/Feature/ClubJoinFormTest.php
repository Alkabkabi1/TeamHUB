<?php

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\User;

test('guest is redirected to login when visiting join form', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $this->get(route('clubs.join.create', $club))
        ->assertRedirect(route('login'));
});

test('authenticated user can view join form with club props', function () {
    $user = User::factory()->create(['email' => 'applicant@uqu.edu.sa']);
    $club = Club::factory()->create(['name' => 'نادي الحاسبات', 'status' => 'active']);

    $this->actingAs($user)
        ->get(route('clubs.join.create', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubJoinForm')
            ->where('club.name', 'نادي الحاسبات')
            ->where('defaults.full_name', $user->name)
            ->where('defaults.university_email', 'applicant@uqu.edu.sa')
        );
});

test('join form returns not found for inactive club', function () {
    $user = User::factory()->create();
    $club = Club::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('clubs.join.create', $club))
        ->assertNotFound();
});

test('authenticated user can submit join application', function () {
    $user = User::factory()->create([
        'name' => 'وئام راشد',
        'email' => 'applicant@uqu.edu.sa',
    ]);
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($user))
        ->assertRedirect(route('clubs.show', $club));

    $application = ClubJoinApplication::query()
        ->where('user_id', $user->id)
        ->where('club_id', $club->id)
        ->first();

    expect($application)->not->toBeNull()
        ->and($application->status)->toBe('pending')
        ->and($application->weekly_hours)->toBe(4);
});

test('join application requires matching university email', function () {
    $user = User::factory()->create(['email' => 'student@uqu.edu.sa']);
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($user, [
            'university_email' => 'other@uqu.edu.sa',
        ]))
        ->assertSessionHasErrors('university_email');
});

test('join application requires uqu email domain', function () {
    $user = User::factory()->create(['email' => 'student@uqu.edu.sa']);
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($user, [
            'university_email' => 'student@gmail.com',
        ]))
        ->assertSessionHasErrors('university_email');
});

test('join application validates required fields', function () {
    $user = User::factory()->create(['email' => 'student@uqu.edu.sa']);
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), [])
        ->assertSessionHasErrors([
            'full_name',
            'university_email',
            'phone',
            'level',
            'major',
            'skills',
            'weekly_hours',
            'tools',
            'motivation',
            'contribution',
        ]);
});

test('duplicate pending application is rejected', function () {
    $user = User::factory()->create(['email' => 'dup@uqu.edu.sa']);
    $club = Club::factory()->create(['status' => 'active']);

    ClubJoinApplication::factory()->pending()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
        'university_email' => $user->email,
    ]);

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($user))
        ->assertSessionHasErrors('club');
});

test('existing club membership blocks new application', function () {
    $user = User::factory()->create(['email' => 'member@uqu.edu.sa']);
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::create([
        'user_id' => $user->id,
        'club_id' => $club->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($user))
        ->assertSessionHasErrors('club');
});

test('join application rejected for inactive club', function () {
    $user = User::factory()->create(['email' => 'student@uqu.edu.sa']);
    $club = Club::factory()->inactive()->create();

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($user))
        ->assertSessionHasErrors('club');
});

test('university staff gets 403 when posting a join application', function () {
    // Only students may apply to join a club; staff are not club members.
    $user = User::factory()->universityStaff()->create(['email' => 'staff@uqu.edu.sa']);
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($user))
        ->assertForbidden();
});
