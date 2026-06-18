<?php

use App\Models\Club;
use App\Models\ClubResource;
use App\Models\Event;
use App\Models\Post;

use function Pest\Laravel\getJson;

it('returns publicly-visible matches grouped by entity to a guest', function () {
    $club = Club::factory()->create(['name' => 'Zephyr Robotics Club', 'status' => 'active']);
    $event = Event::factory()->for($club)->create(['title' => 'Zephyr Launch Night', 'status' => 'active']);
    $post = Post::factory()->for($club)->create(['title' => 'Zephyr Wins Award', 'published_at' => now()->subDay()]);
    $resource = ClubResource::factory()->for($club)->create(['title' => 'Zephyr Handbook']);

    getJson('/search?q=Zephyr')
        ->assertOk()
        ->assertJsonPath('groups.clubs.0.id', $club->id)
        ->assertJsonPath('groups.clubs.0.url', route('clubs.show', $club))
        ->assertJsonPath('groups.events.0.id', $event->id)
        ->assertJsonPath('groups.news.0.id', $post->id)
        ->assertJsonPath('groups.resources.0.id', $resource->id);
});

it('excludes records that are not publicly visible', function () {
    Club::factory()->inactive()->create(['name' => 'Hidden Phoenix Club']);
    Event::factory()->create(['title' => 'Hidden Phoenix Gala', 'status' => 'draft']);
    // News has no drafts (published_at is NOT NULL); a future date is "not yet visible".
    Post::factory()->create(['title' => 'Hidden Phoenix Future', 'published_at' => now()->addWeek()]);

    getJson('/search?q=Phoenix')
        ->assertOk()
        ->assertJsonCount(0, 'groups.clubs')
        ->assertJsonCount(0, 'groups.events')
        ->assertJsonCount(0, 'groups.news');
});

it('returns empty groups when the query is shorter than two characters', function () {
    Club::factory()->create(['name' => 'A Club', 'status' => 'active']);

    getJson('/search?q=a')
        ->assertOk()
        ->assertJsonCount(0, 'groups.clubs')
        ->assertJsonCount(0, 'groups.events')
        ->assertJsonCount(0, 'groups.news')
        ->assertJsonCount(0, 'groups.resources');
});
