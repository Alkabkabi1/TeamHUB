<?php

use App\Models\Club;
use App\Models\Tag;
use App\Models\User;

test('clubs page renders active clubs with stats and tag/sort filters', function () {
    $popular = Club::factory()->create([
        'name' => 'نادي الحاسبات',
        'category' => 'تقني',
        'status' => 'active',
    ]);
    Club::factory()->inactive()->create(['name' => 'نادي مخفي']);

    User::factory()->count(2)->create()->each(fn (User $user) => $popular->memberships()->create([
        'user_id' => $user->id,
        'role' => 'member',
        'joined_at' => now(),
    ]));

    $this->get(route('clubs'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubsPage')
            ->has('clubs', 1)
            ->where('clubs.0.name', 'نادي الحاسبات')
            ->where('clubs.0.members_count', 2)
            ->where('stats.clubs', 1)
            ->where('stats.members', 2)
            ->where('filters.sort', 'members')
            ->has('filters.tag')
            ->has('filterOptions.tags')
            ->has('filterOptions.sorts')
            ->has('stats.projects')
            ->has('stats.open_tasks')
        );
});

test('clubs page filters by name search', function () {
    Club::factory()->create(['name' => 'نادي الحاسبات', 'status' => 'active']);
    Club::factory()->create(['name' => 'نادي الطب', 'status' => 'active']);

    $this->get(route('clubs', ['search' => 'الحاسبات']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubsPage')
            ->has('clubs', 1)
            ->where('clubs.0.name', 'نادي الحاسبات')
            ->where('filters.search', 'الحاسبات')
        );
});

test('clubs page filters by tag', function () {
    $tag = Tag::factory()->create(['name' => 'برمجة']);

    $tagged = Club::factory()->create(['name' => 'نادي الحاسبات', 'status' => 'active']);
    $tagged->tags()->attach($tag);

    Club::factory()->create(['name' => 'نادي الطب', 'status' => 'active']);

    $this->get(route('clubs', ['tag' => $tag->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubsPage')
            ->has('clubs', 1)
            ->where('clubs.0.name', 'نادي الحاسبات')
            ->where('filters.tag', (string) $tag->id)
        );
});

test('clubs page sorts by name and preserves the selected sort', function () {
    Club::factory()->create(['name' => 'باء نادي', 'status' => 'active']);
    Club::factory()->create(['name' => 'ألف نادي', 'status' => 'active']);

    $this->get(route('clubs', ['sort' => 'name']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubsPage')
            ->has('clubs', 2)
            ->where('clubs.0.name', 'ألف نادي')
            ->where('filters.sort', 'name')
        );
});

test('clubs page ignores an unknown sort and falls back to members', function () {
    Club::factory()->create(['name' => 'نادي', 'status' => 'active']);

    $this->get(route('clubs', ['sort' => 'bogus']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubsPage')
            ->where('filters.sort', 'members')
        );
});

test('resources page renders empty downloads and media state', function () {
    $this->get(route('resources'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ResourcesPage')
            ->has('downloads', 0)
            ->has('media', 0)
            ->has('filters.search')
            ->has('filterOptions.tags')
            ->has('filterOptions.sorts')
        );
});

test('resources page preserves search filters while empty', function () {
    $this->get(route('resources', ['search' => 'بايثون']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ResourcesPage')
            ->has('downloads', 0)
            ->has('media', 0)
            ->where('filters.search', 'بايثون')
        );
});

test('resources page preserves sort filters while empty', function () {
    $this->get(route('resources', ['sort' => 'oldest']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ResourcesPage')
            ->has('downloads', 0)
            ->has('media', 0)
            ->where('filters.sort', 'oldest')
        );
});
