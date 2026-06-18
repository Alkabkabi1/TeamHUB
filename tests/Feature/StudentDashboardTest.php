<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerHour;
use Database\Seeders\DatabaseSeeder;

test('guest is redirected to login when visiting student dashboard', function () {
    $this->get(route('student-dashboard'))
        ->assertRedirect(route('login'));
});

test('student dashboard shows volunteer hours and clubs from database', function () {
    $user = User::factory()->student()->create([
        'name' => 'Demo Student',
        'email' => 'phase1-student@uqu.edu.sa',
    ]);
    $club = Club::factory()->create(['name' => 'نادي الحاسبات', 'status' => 'active']);
    $joinedAt = now()->subYear();

    ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
        'joined_at' => $joinedAt,
        'requested_at' => $joinedAt,
        'reviewed_at' => $joinedAt,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    VolunteerHour::factory()->create([
        'user_id' => $user->id,
        'event_id' => $pastEvent->id,
        'hours' => 5.5,
        'approved_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StudentDashboard')
            ->where('totalHours', 5.5)
            ->where('profile.name', 'Demo Student')
            ->where('profile.email', 'phase1-student@uqu.edu.sa')
            ->where('profile.joinedAt', fn ($value) => $value !== null)
            ->where('stats.clubsCount', 1)
            ->where('stats.totalHours', 5.5)
            ->has('clubs', 1)
            ->where('clubs.0.name', 'نادي الحاسبات')
            ->where('clubs.0.volunteerHours', 5.5)
            ->where('clubs.0.memberSince', $joinedAt->format('Y'))
        );
});

test('student dashboard lists upcoming active events as featured and excludes past ones', function () {
    $user = User::factory()->student()->create();
    $club = Club::factory()->create(['name' => 'نادي الفنون', 'status' => 'active']);

    $upcoming = Event::factory()->upcoming()->for($club)->create([
        'title' => 'ورشة الرسم',
        'status' => 'active',
    ]);
    Event::factory()->past()->for($club)->create(['status' => 'active']);

    $this->actingAs($user)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StudentDashboard')
            ->has('featuredEvents', 1)
            ->where('featuredEvents.0.id', $upcoming->id)
            ->where('featuredEvents.0.title', 'ورشة الرسم')
            ->where('featuredEvents.0.clubName', 'نادي الفنون')
        );
});

test('seeded demo student sees volunteer hours on dashboard', function () {
    $this->seed(DatabaseSeeder::class);

    $student = User::query()->where('email', 'student@uqu.edu.sa')->first();

    expect($student)->not->toBeNull();

    $this->actingAs($student)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StudentDashboard')
            ->where('totalHours', fn ($hours) => $hours > 0)
            ->where('stats.clubsCount', fn ($count) => $count >= 2)
        );
});
