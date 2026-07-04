<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;

test('club show page returns club data from database', function () {
    $club = Club::factory()->create([
        'name' => 'نادي الحاسبات',
        'status' => 'active',
        'category' => 'تقني',
    ]);

    $committee = Committee::factory()->for($club)->create();
    Task::factory()->count(2)->for($committee)->create(['status' => 'todo']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('club.name', 'نادي الحاسبات')
            ->where('club.category', 'تقني')
            ->where('stats.members_count', 0)
            ->where('stats.projects_count', 1)
            ->where('stats.open_tasks_count', 2)
        );
});

test('club show page includes member count', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $member = User::factory()->create();

    ClubMembership::create([
        'user_id' => $member->id,
        'club_id' => $club->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.members_count', 1)
        );
});

test('club show page lists committee project updates', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->for($club)->create();

    Post::factory()->create([
        'club_id' => $club->id,
        'committee_id' => $committee->id,
        'title' => 'Project Update',
        'published_at' => now(),
    ]);

    Post::factory()->create(['title' => 'Other Club Post']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->has('recentUpdates', 1)
            ->where('recentUpdates.0.title', 'Project Update')
        );
});

test('club show page recent updates is empty when club has no project posts', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->has('recentUpdates', 0)
        );
});
