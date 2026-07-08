<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated students are redirected from dashboard to my tasks', function () {
    $user = User::factory()->member()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('my-tasks'));
});

test('authenticated admins can access the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
});
