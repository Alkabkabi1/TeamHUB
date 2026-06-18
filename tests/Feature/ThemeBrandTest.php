<?php

use App\Models\Club;

test('the app never renders in dark mode', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('class="dark"', false);
});

test('the app stays light even when a stale dark cookie is present', function () {
    $this->withUnencryptedCookies(['appearance' => 'dark'])
        ->get('/')
        ->assertOk()
        ->assertDontSee('class="dark"', false);
});

test('shared theme prop exposes the university default brand color', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('theme.brand', config('theme.brand'))
        );
});

test('club page overrides the brand color with the club theme', function () {
    $club = Club::factory()->create([
        'status' => 'active',
        'theme' => '#123456',
    ]);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('theme.brand', '#123456')
        );
});

test('club page falls back to the default brand when the club has no theme', function () {
    $club = Club::factory()->create([
        'status' => 'active',
        'theme' => null,
    ]);

    $this->get(route('clubs.show', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('theme.brand', config('theme.brand'))
        );
});
