<?php

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\ClubResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('club has expected relationships', function () {
    $club = Club::factory()->create();

    expect($club->events())->toBeInstanceOf(HasMany::class)
        ->and($club->memberships())->toBeInstanceOf(HasMany::class)
        ->and($club->joinApplications())->toBeInstanceOf(HasMany::class)
        ->and($club->resources())->toBeInstanceOf(HasMany::class)
        ->and($club->members())->toBeInstanceOf(BelongsToMany::class);
});

test('club can load related join applications and resources', function () {
    $club = Club::factory()->create();

    ClubJoinApplication::factory()->count(2)->create(['club_id' => $club->id]);
    ClubResource::factory()->count(3)->create(['club_id' => $club->id]);
    Event::factory()->count(2)->for($club)->create();

    $club->load(['joinApplications', 'resources', 'events']);

    expect($club->joinApplications)->toHaveCount(2)
        ->and($club->resources)->toHaveCount(3)
        ->and($club->events)->toHaveCount(2);
});

test('force deleting club cascades to join applications and resources', function () {
    $club = Club::factory()->create();

    ClubJoinApplication::factory()->create(['club_id' => $club->id]);
    ClubResource::factory()->create(['club_id' => $club->id]);
    Event::factory()->for($club)->create();

    $clubId = $club->id;
    $club->forceDelete();

    expect(ClubJoinApplication::query()->where('club_id', $clubId)->exists())->toBeFalse()
        ->and(ClubResource::query()->where('club_id', $clubId)->exists())->toBeFalse()
        ->and(Event::query()->where('club_id', $clubId)->exists())->toBeFalse();
});

test('club members relationship uses club memberships pivot', function () {
    $club = Club::factory()->create();
    $user = User::factory()->create();

    ClubMembership::create([
        'user_id' => $user->id,
        'club_id' => $club->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    expect($club->members)->toHaveCount(1)
        ->and($club->members->first()->id)->toBe($user->id);
});
