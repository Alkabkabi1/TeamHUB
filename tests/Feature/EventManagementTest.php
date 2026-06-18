<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeSupervisorForClub(Club $club): User
{
    $supervisor = User::factory()->clubSupervisor()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    return $supervisor;
}

// ─── Create ─────────────────────────────────────────────────────────────────

test('guest cannot access event create form', function () {
    $club = Club::factory()->create();

    $this->get(route('events.create', $club))
        ->assertRedirect(route('login'));
});

test('non-supervisor gets 403 on event create form', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('events.create', $club))
        ->assertForbidden();
});

test('supervisor can access event create form', function () {
    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);

    $this->actingAs($supervisor)
        ->get(route('events.create', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('EventForm')
            ->where('mode', 'create')
            ->has('club')
        );
});

// ─── Store ───────────────────────────────────────────────────────────────────

test('non-supervisor gets 403 when storing an event', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('events.store', $club), [
            'title' => 'Test Event',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ])
        ->assertForbidden();
});

test('supervisor can store a new event', function () {
    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);

    $startsAt = now()->addDay();
    $endsAt = $startsAt->copy()->addHours(2);

    $this->actingAs($supervisor)
        ->post(route('events.store', $club), [
            'title' => 'Tech Workshop',
            'description' => 'An awesome workshop',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'location' => 'Building A',
            'capacity' => 50,
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('events', [
        'club_id' => $club->id,
        'title' => 'Tech Workshop',
        'location' => 'Building A',
        'capacity' => 50,
        'status' => 'active',
    ]);
});

test('supervisor can store an event with multiple images', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);

    $startsAt = now()->addDay();
    $endsAt = $startsAt->copy()->addHours(2);

    $this->actingAs($supervisor)
        ->post(route('events.store', $club), [
            'title' => 'Event with gallery',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'status' => 'active',
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ])
        ->assertRedirect();

    $event = Event::where('club_id', $club->id)->first();
    expect($event->getMedia(Event::IMAGE_COLLECTION))->toHaveCount(2);
});

test('store event rejects an image larger than 10MB', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);

    $startsAt = now()->addDay();

    $this->actingAs($supervisor)
        ->post(route('events.store', $club), [
            'title' => 'Event with large image',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $startsAt->copy()->addHours(2)->toDateTimeString(),
            'status' => 'active',
            'images' => [UploadedFile::fake()->image('big.jpg')->size(10241)],
        ])
        ->assertSessionHasErrors('images.0');
});

test('store event validation fails without title', function () {
    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);

    $this->actingAs($supervisor)
        ->post(route('events.store', $club), [
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ])
        ->assertSessionHasErrors('title');
});

test('store event validation fails when ends_at is before starts_at', function () {
    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);

    $this->actingAs($supervisor)
        ->post(route('events.store', $club), [
            'title' => 'Bad Event',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('ends_at');
});

// ─── Edit ────────────────────────────────────────────────────────────────────

test('supervisor can access event edit form', function () {
    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);
    $event = Event::factory()->for($club)->upcoming()->create();

    $this->actingAs($supervisor)
        ->get(route('events.edit', [$club, $event]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('EventForm')
            ->where('mode', 'edit')
            ->has('event')
            ->where('event.id', $event->id)
        );
});

test('non-supervisor gets 403 on event edit form', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();
    $event = Event::factory()->for($club)->upcoming()->create();

    $this->actingAs($student)
        ->get(route('events.edit', [$club, $event]))
        ->assertForbidden();
});

test('edit returns 404 when event does not belong to club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);
    $event = Event::factory()->for($otherClub)->upcoming()->create();

    $this->actingAs($supervisor)
        ->get(route('events.edit', [$club, $event]))
        ->assertNotFound();
});

// ─── Update ──────────────────────────────────────────────────────────────────

test('supervisor can update an event', function () {
    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);
    $event = Event::factory()->for($club)->upcoming()->create(['title' => 'Old Title']);

    $startsAt = now()->addDay();
    $endsAt = $startsAt->copy()->addHours(3);

    $this->actingAs($supervisor)
        ->put(route('events.update', [$club, $event]), [
            'title' => 'New Title',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'title' => 'New Title',
    ]);
});

test('non-supervisor gets 403 when updating an event', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();
    $event = Event::factory()->for($club)->upcoming()->create();

    $this->actingAs($student)
        ->put(route('events.update', [$club, $event]), [
            'title' => 'Hacked',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ])
        ->assertForbidden();
});

test('supervisor from another club gets 403 when updating an event', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();
    $supervisor = makeSupervisorForClub($otherClub);
    $event = Event::factory()->for($club)->upcoming()->create();

    $this->actingAs($supervisor)
        ->put(route('events.update', [$club, $event]), [
            'title' => 'Sneaky Update',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ])
        ->assertForbidden();
});

test('update returns 404 when event does not belong to club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);
    $event = Event::factory()->for($otherClub)->upcoming()->create();

    $this->actingAs($supervisor)
        ->put(route('events.update', [$club, $event]), [
            'title' => 'Sneaky',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ])
        ->assertNotFound();
});

// ─── Destroy ─────────────────────────────────────────────────────────────────

test('supervisor can delete an event', function () {
    $club = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);
    $event = Event::factory()->for($club)->upcoming()->create();

    $this->actingAs($supervisor)
        ->delete(route('events.destroy', [$club, $event]))
        ->assertRedirect();

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('non-supervisor gets 403 when deleting an event', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();
    $event = Event::factory()->for($club)->upcoming()->create();

    $this->actingAs($student)
        ->delete(route('events.destroy', [$club, $event]))
        ->assertForbidden();
});

test('destroy returns 404 when event does not belong to club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();
    $supervisor = makeSupervisorForClub($club);
    $event = Event::factory()->for($otherClub)->upcoming()->create();

    $this->actingAs($supervisor)
        ->delete(route('events.destroy', [$club, $event]))
        ->assertNotFound();
});
