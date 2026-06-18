<?php

use App\Models\Club;
use App\Models\ClubResource;
use App\Models\Tag;

test('resources page shows downloads and media from database', function () {
    $club = Club::factory()->create(['name' => 'نادي الحاسبات']);

    ClubResource::factory()->download()->create([
        'club_id' => $club->id,
        'title' => 'دليل الانضمام',
        'description' => 'ملف تعريفي',
        'format' => 'PDF',
        'access' => 'عام',
    ]);

    ClubResource::factory()->media()->create([
        'club_id' => $club->id,
        'title' => 'معرض الصور',
        'format' => 'PNG',
        'access' => 'عام',
    ]);

    $this->get(route('resources'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ResourcesPage')
            ->has('downloads', 1)
            ->has('media', 1)
            ->where('downloads.0.name', 'دليل الانضمام')
            ->where('downloads.0.description', 'ملف تعريفي')
            ->where('media.0.title', 'معرض الصور')
            ->where('media.0.club', 'نادي الحاسبات')
        );
});

test('resources page renders empty state when no resources exist', function () {
    $this->get(route('resources'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ResourcesPage')
            ->has('downloads', 0)
            ->has('media', 0)
            ->has('filterOptions.tags')
            ->has('filterOptions.sorts')
        );
});

test('resources page filters by search query', function () {
    $club = Club::factory()->create();

    ClubResource::factory()->download()->create([
        'club_id' => $club->id,
        'title' => 'دليل بايثون',
        'format' => 'PDF',
    ]);

    ClubResource::factory()->download()->create([
        'club_id' => $club->id,
        'title' => 'دليل التصميم',
        'format' => 'PDF',
    ]);

    $this->get(route('resources', ['search' => 'بايثون']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('downloads', 1)
            ->where('downloads.0.name', 'دليل بايثون')
            ->where('filters.search', 'بايثون')
        );
});

test('resources page filters by tag', function () {
    $tag = Tag::factory()->create(['name' => 'برمجة']);
    $club = Club::factory()->create();

    $tagged = ClubResource::factory()->download()->create([
        'club_id' => $club->id,
        'title' => 'مورد مصنف',
    ]);
    $tagged->tags()->attach($tag);

    ClubResource::factory()->download()->create([
        'club_id' => $club->id,
        'title' => 'مورد غير مصنف',
    ]);

    $this->get(route('resources', ['tag' => $tag->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('downloads', 1)
            ->where('downloads.0.name', 'مورد مصنف')
            ->where('filters.tag', (string) $tag->id)
        );
});

test('resources page sorts media by title and preserves the selected sort', function () {
    $club = Club::factory()->create();

    ClubResource::factory()->media()->create([
        'club_id' => $club->id,
        'title' => 'باء وسائط',
    ]);

    ClubResource::factory()->media()->create([
        'club_id' => $club->id,
        'title' => 'ألف وسائط',
    ]);

    $this->get(route('resources', ['sort' => 'title']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('media', 2)
            ->where('media.0.title', 'ألف وسائط')
            ->where('filters.sort', 'title')
        );
});

test('resources page builds tag filter options from attached tags', function () {
    $tags = Tag::factory()->count(2)->create();
    $club = Club::factory()->create();

    $resource = ClubResource::factory()->download()->create(['club_id' => $club->id]);
    $resource->tags()->attach($tags);

    $this->get(route('resources'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('filterOptions.tags', 2)
            ->has('filterOptions.sorts', 3)
        );
});

test('resources page keeps downloads and media separate by type', function () {
    $club = Club::factory()->create();

    ClubResource::factory()->download()->create([
        'club_id' => $club->id,
        'title' => 'ملف PDF',
        'format' => 'PDF',
    ]);

    ClubResource::factory()->media()->create([
        'club_id' => $club->id,
        'title' => 'صورة PNG',
        'format' => 'PNG',
    ]);

    $this->get(route('resources'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('downloads', 1)
            ->where('downloads.0.name', 'ملف PDF')
            ->has('media', 1)
            ->where('media.0.title', 'صورة PNG')
        );
});
