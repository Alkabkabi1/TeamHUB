<?php

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\ClubResource;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('database seeder populates demo users and clubs', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'student@teamhub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'club-leader@teamhub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'committee-leader@teamhub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'admin@teamhub.test')->exists())->toBeTrue()
        ->and(Club::query()->where('name', 'نادي الحاسبات')->exists())->toBeTrue()
        ->and(Club::count())->toBeGreaterThanOrEqual(6);
});

test('database seeder populates memberships and resources', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ClubMembership::count())->toBeGreaterThan(0)
        ->and(ClubResource::count())->toBeGreaterThan(0)
        ->and(Post::count())->toBeGreaterThan(0)
        ->and(ClubJoinApplication::query()->where('status', 'pending')->exists())->toBeTrue();
});

test('student demo user has club memberships after seeding', function () {
    $this->seed(DatabaseSeeder::class);

    $student = User::query()->where('email', 'student@teamhub.test')->first();

    expect($student)->not->toBeNull()
        ->and($student->clubMemberships()->count())->toBeGreaterThanOrEqual(2);
});

test('seeded club memberships are approved for active members', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ClubMembership::query()->where('status', 'approved')->whereNotNull('joined_at')->count())
        ->toBeGreaterThan(0);
});

test('seeded data includes pending club memberships for cs club', function () {
    $this->seed(DatabaseSeeder::class);

    $csClub = Club::query()->where('name', 'نادي الحاسبات')->first();

    expect($csClub)->not->toBeNull()
        ->and(ClubMembership::query()
            ->where('club_id', $csClub->id)
            ->where('status', 'pending')
            ->count())->toBeGreaterThanOrEqual(1);
});

test('seeded data includes rejected join applications', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ClubJoinApplication::query()->where('status', 'rejected')->exists())->toBeTrue();
});

test('seeded data includes approved join applications', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ClubJoinApplication::query()->where('status', 'approved')->exists())->toBeTrue();
});

test('database seeder can run twice without errors', function () {
    $this->seed(DatabaseSeeder::class);

    $clubsAfterFirst = Club::count();

    expect(fn () => $this->seed(DatabaseSeeder::class))->not->toThrow(Throwable::class)
        ->and(Club::count())->toBe($clubsAfterFirst);
});
