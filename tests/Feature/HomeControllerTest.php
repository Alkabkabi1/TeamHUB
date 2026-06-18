<?php

use App\Models\Club;
use App\Models\Event;
use App\Models\User;

test('home page returns 200', function () {
    $this->get(route('home'))->assertOk();
});

test('home page renders the Welcome component with clubs, events and filters', function () {
    Club::factory()->count(3)->create();
    $club = Club::query()->firstOrFail();
    Event::factory()->upcoming()->count(2)->for($club)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('clubs', 3)
            ->has('events', 2)
            ->has('filters.search')
            ->where('filters.search', '')
            ->has('filters.category')
            ->has('filters.college')
            ->has('filters.status')
            ->has('filterOptions.categories')
            ->has('filterOptions.colleges')
            ->has('filterOptions.statuses')
        );
});

test('home page filters clubs by search query in name', function () {
    Club::factory()->create(['name' => 'نادي الحاسبات']);
    Club::factory()->create(['name' => 'نادي الفنون']);

    $this->get(route('home', ['search' => 'حاسبات']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'نادي الحاسبات')
            ->where('filters.search', 'حاسبات')
        );
});

test('home page filters events by search query in title or description', function () {
    $club = Club::factory()->create();
    Event::factory()->upcoming()->for($club)->create([
        'title' => 'ورشة برمجة',
        'description' => 'تعلم البرمجة من الصفر',
    ]);
    Event::factory()->upcoming()->for($club)->create([
        'title' => 'معرض الفنون',
        'description' => 'معرض سنوي للفنون التشكيلية',
    ]);

    $this->get(route('home', ['search' => 'برمجة']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.title', 'ورشة برمجة')
        );
});

test('home page hides past events from the upcoming list', function () {
    $club = Club::factory()->create();
    Event::factory()->upcoming()->for($club)->create(['title' => 'فعالية قادمة']);
    Event::factory()->past()->for($club)->create(['title' => 'فعالية ماضية']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.title', 'فعالية قادمة')
        );
});

test('home page orders clubs by members_count descending', function () {
    $popular = Club::factory()->create(['name' => 'نادي مشهور']);
    $small = Club::factory()->create(['name' => 'نادي صغير']);

    User::factory()->count(3)->create()->each(fn (User $user) => $popular->memberships()->create([
        'user_id' => $user->id,
        'role' => 'member',
        'joined_at' => now(),
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

test('home page returns empty arrays when database is empty', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 0)
            ->has('events', 0)
        );
});

test('home page caps results to 8 clubs and 4 events when no search', function () {
    Club::factory()->count(12)->create();
    $club = Club::factory()->create();
    Event::factory()->upcoming()->count(10)->for($club)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 8)
            ->has('events', 4)
        );
});

test('home page widens caps when a search term is supplied', function () {
    Club::factory()->count(12)->sequence(fn ($sequence) => [
        'name' => 'نادي رقم '.($sequence->index + 1),
    ])->create();

    $this->get(route('home', ['search' => 'نادي']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('clubs', 12));
});

test('home page exposes members_count and club relation on each row', function () {
    $club = Club::factory()->create(['name' => 'نادي تجريبي']);
    Event::factory()->upcoming()->for($club)->create(['title' => 'فعالية تجريبية']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs.0.members_count')
            ->where('events.0.club.name', 'نادي تجريبي')
        );
});

test('home page lists only active clubs by default', function () {
    Club::factory()->create(['name' => 'ظاهر', 'status' => 'active']);
    Club::factory()->inactive()->create(['name' => 'مخفي']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'ظاهر')
        );
});

test('home page allows viewing inactive clubs when status filter is set', function () {
    Club::factory()->inactive()->create(['name' => 'مخفي']);

    $this->get(route('home', ['status' => 'inactive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'مخفي')
            ->where('filters.status', 'inactive')
        );
});

test('home page filters clubs by category', function () {
    Club::factory()->create(['name' => 'أ', 'category' => 'تقني', 'status' => 'active']);
    Club::factory()->create(['name' => 'ب', 'category' => 'رياضي', 'status' => 'active']);

    $this->get(route('home', ['category' => 'تقني']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'أ')
            ->where('filters.category', 'تقني')
        );
});

test('home page filters clubs by college', function () {
    Club::factory()->create([
        'name' => 'نادي الحاسبات',
        'college' => 'كلية الحاسبات والمعلومات',
        'status' => 'active',
    ]);
    Club::factory()->create([
        'name' => 'نادي الطب',
        'college' => 'كلية الطب',
        'status' => 'active',
    ]);

    $this->get(route('home', ['college' => 'كلية الطب']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clubs', 1)
            ->where('clubs.0.name', 'نادي الطب')
        );
});

test('home page filters upcoming events by club filters', function () {
    $cs = Club::factory()->create([
        'name' => 'نادي تقني',
        'category' => 'تقني',
        'status' => 'active',
    ]);
    $arts = Club::factory()->create([
        'name' => 'نادي فني',
        'category' => 'ثقافي',
        'status' => 'active',
    ]);

    Event::factory()->upcoming()->for($cs)->create(['title' => 'برمجة']);
    Event::factory()->upcoming()->for($arts)->create(['title' => 'فن']);

    $this->get(route('home', ['category' => 'تقني']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.title', 'برمجة')
        );
});

test('home page widens caps when category filter is applied', function () {
    foreach (range(1, 12) as $i) {
        Club::factory()->create([
            'name' => 'نادي '.$i,
            'category' => 'موحد',
            'status' => 'active',
        ]);
    }

    $this->get(route('home', ['category' => 'موحد']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('clubs', 12));
});

test('home page filterOptions categories and colleges are collections not plain arrays', function () {
    Club::factory()->create(['category' => 'تقني', 'college' => 'كلية الحاسبات']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('filterOptions.categories')
            ->has('filterOptions.colleges')
        );
});
