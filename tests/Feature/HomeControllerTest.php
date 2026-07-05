<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

beforeEach(function () {
    config()->set('demo.quick_login', false);
});

test('home page returns 200', function () {
    $this->get(route('home'))->assertOk();
});

test('home page renders the Welcome component with clubs and filters', function () {
    Workspace::factory()->count(3)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('clubs', 3)
            ->has('filters.search')
            ->where('filters.search', '')
            ->has('filters.status')
            ->has('filterOptions.statuses')
        );
});

test('home page filters clubs by search query in name', function () {
    Workspace::factory()->create(['name' => 'مساحة الحاسبات']);
    Workspace::factory()->create(['name' => 'مساحة الفنون']);

    $this->get(route('home', ['search' => 'حاسبات']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'مساحة الحاسبات')
            ->where('filters.search', 'حاسبات')
        );
});

test('home page orders clubs by members_count descending', function () {
    $popular = Workspace::factory()->create(['name' => 'مساحة مشهورة']);
    $small = Workspace::factory()->create(['name' => 'مساحة صغيرة']);

    User::factory()->count(3)->create()->each(fn (User $user) => WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $popular->id,
    ]));

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clubs.0.id', $popular->id)
            ->where('clubs.0.members_count', 3)
            ->where('clubs.1.id', $small->id)
            ->where('clubs.1.members_count', 0)
        );
});

test('home page returns empty clubs when database is empty', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 0)
        );
});

test('home page caps results to 8 clubs when no search', function () {
    Workspace::factory()->count(12)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 8)
        );
});

test('home page widens caps when a search term is supplied', function () {
    Workspace::factory()->count(12)->sequence(fn ($sequence) => [
        'name' => 'مساحة رقم '.($sequence->index + 1),
    ])->create();

    $this->get(route('home', ['search' => 'مساحة']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('clubs', 12));
});

test('home page exposes members_count on each row', function () {
    Workspace::factory()->create(['name' => 'مساحة تجريبية']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs.0.members_count')
        );
});

test('home page lists only active clubs by default', function () {
    Workspace::factory()->create(['name' => 'ظاهر', 'status' => 'active']);
    Workspace::factory()->inactive()->create(['name' => 'مخفي']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'ظاهر')
        );
});

test('home page allows viewing inactive clubs when status filter is set', function () {
    Workspace::factory()->inactive()->create(['name' => 'مخفي']);

    $this->get(route('home', ['status' => 'inactive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'مخفي')
            ->where('filters.status', 'inactive')
        );
});
