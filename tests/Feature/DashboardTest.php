<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated students are redirected from the generic dashboard to their dashboard', function () {
    $user = User::factory()->create(['role' => 'student']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('student-dashboard'));
});

test('authenticated university staff are redirected from the generic dashboard to the filament panel', function () {
    $user = User::factory()->universityStaff()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('filament.admin.pages.dashboard'));
});
