<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demo:reset is skipped when the flag is disabled', function () {
    config()->set('demo.hourly_reset', false);

    $user = User::factory()->create();

    $this->artisan('demo:reset')->assertSuccessful();

    // Nothing was wiped — the guard returned before migrate:fresh ran.
    expect(User::whereKey($user->id)->exists())->toBeTrue();
});

test('demo:reset refuses to run in production even when enabled', function () {
    config()->set('demo.hourly_reset', true);
    $this->app['env'] = 'production';

    $user = User::factory()->create();

    $this->artisan('demo:reset')->assertFailed();

    expect(User::whereKey($user->id)->exists())->toBeTrue();
});
