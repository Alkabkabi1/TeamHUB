<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use App\Models\VolunteerHour;

test('club show page returns club data from database', function () {
    $club = Club::factory()->create([
        'name' => 'نادي الحاسبات',
        'status' => 'active',
        'category' => 'تقني',
    ]);

    Event::factory()->upcoming()->count(2)->for($club)->create();

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('club.name', 'نادي الحاسبات')
            ->where('club.category', 'تقني')
            ->has('upcomingEvents', 2)
            ->where('stats.members_count', 0)
            ->where('stats.upcoming_events_count', 2)
        );
});

test('club show page includes member count and volunteer hours', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $member = User::factory()->create();

    ClubMembership::create([
        'user_id' => $member->id,
        'club_id' => $club->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);
    VolunteerHour::create([
        'user_id' => $member->id,
        'club_id' => $club->id,
        'event_id' => $pastEvent->id,
        'hours' => 5.5,
        'approved_at' => now(),
    ]);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.members_count', 1)
            ->where('stats.volunteer_hours_sum', 5.5)
        );
});

test('club show page only lists active upcoming events', function () {
    $club = Club::factory()->create(['status' => 'active']);

    Event::factory()->upcoming()->for($club)->create(['title' => 'فعالية قادمة', 'status' => 'active']);
    Event::factory()->upcoming()->for($club)->create(['title' => 'فعالية ملغاة', 'status' => 'cancelled']);
    Event::factory()->past()->for($club)->create(['title' => 'فعالية قديمة', 'status' => 'active']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('upcomingEvents', 1)
            ->where('upcomingEvents.0.title', 'فعالية قادمة')
        );
});

test('club show page formats event cards with club name', function () {
    $club = Club::factory()->create(['name' => 'نادي البيئة', 'status' => 'active']);
    Event::factory()->upcoming()->for($club)->create([
        'title' => 'يوم التشجير',
        'description' => 'فعالية بيئية',
    ]);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('upcomingEvents.0.club', 'نادي البيئة')
            ->where('upcomingEvents.0.title', 'يوم التشجير')
            ->where('upcomingEvents.0.description', 'فعالية بيئية')
        );
});

test('club show page passes a posts prop with the club posts', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $post1 = Post::factory()->create([
        'club_id' => $club->id,
        'title' => 'First Post',
        'published_at' => now()->subDays(2),
    ]);

    $post2 = Post::factory()->create([
        'club_id' => $club->id,
        'title' => 'Second Post',
        'published_at' => now()->subDay(),
    ]);

    // Post from a different club — must not appear
    Post::factory()->create(['title' => 'Other Club Post']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->has('posts', 2)
            ->where('posts.0.id', $post2->id)
            ->where('posts.1.id', $post1->id)
            ->has('posts.0.title')
            ->has('posts.0.excerpt')
            ->has('posts.0.published_at')
        );
});

test('club show page provides active events for the calendar', function () {
    $club = Club::factory()->create(['status' => 'active']);

    Event::factory()->upcoming()->for($club)->create(['title' => 'فعالية قادمة', 'status' => 'active']);
    Event::factory()->past()->for($club)->create(['title' => 'فعالية سابقة', 'status' => 'active']);
    Event::factory()->upcoming()->for($club)->create(['title' => 'فعالية ملغاة', 'status' => 'cancelled']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('calendarEvents', 2)
            ->has('calendarEvents.0.id')
            ->has('calendarEvents.0.title')
            ->has('calendarEvents.0.starts_at')
        );
});

test('club show page posts prop is empty when club has no posts', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->has('posts', 0)
        );
});
