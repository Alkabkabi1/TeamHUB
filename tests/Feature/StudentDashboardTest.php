<?php

use App\Models\User;

test('guest is redirected to login when visiting student dashboard', function () {
    $this->get(route('student-dashboard'))
        ->assertRedirect(route('login'));
});

test('student dashboard redirects to my tasks', function () {
    $user = User::factory()->student()->create();

    $this->actingAs($user)
        ->get(route('student-dashboard'))
        ->assertRedirect(route('my-tasks'));
});
