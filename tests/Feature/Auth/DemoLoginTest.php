<?php

use App\Models\User;

test('a guest can sign in instantly as an allowlisted demo account', function () {
    config()->set('demo.quick_login', true);
    $student = User::factory()->student()->create(['email' => 'student@uqu.edu.sa']);

    $response = $this->post(route('demo.login'), ['email' => $student->email]);

    $this->assertAuthenticatedAs($student);
    $response->assertRedirect(route('student-dashboard', absolute: false));
});

test('the demo login refuses emails outside the allowlist', function () {
    config()->set('demo.quick_login', true);
    $outsider = User::factory()->create(['email' => 'someone-else@uqu.edu.sa']);

    $response = $this->from(route('login'))->post(route('demo.login'), [
        'email' => $outsider->email,
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('the demo login is hidden when the feature is disabled', function () {
    config()->set('demo.quick_login', false);
    User::factory()->student()->create(['email' => 'student@uqu.edu.sa']);

    $response = $this->post(route('demo.login'), ['email' => 'student@uqu.edu.sa']);

    $this->assertGuest();
    $response->assertNotFound();
});

test('the login screen exposes only the seeded allowlisted accounts', function () {
    config()->set('demo.quick_login', true);
    User::factory()->universityStaff()->create([
        'email' => 'admin@uqu.edu.sa',
        'name' => 'د. غفران',
    ]);

    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page
            ->component('auth/Login')
            ->where('demoAccounts.0.email', 'admin@uqu.edu.sa')
            ->where('demoAccounts.0.role', 'university_staff')
            ->where('demoAccounts.0.name', 'د. غفران')
        );
});
