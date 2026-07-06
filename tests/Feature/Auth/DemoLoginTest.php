<?php

use App\Models\User;

test('a guest can sign in instantly as an allowlisted demo account', function () {
    config()->set('demo.quick_login', true);
    $staff = User::factory()->student()->create(['email' => 'staff@teamhub.test']);

    $response = $this->post(route('demo.login'), ['email' => $staff->email]);

    $this->assertAuthenticatedAs($staff);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('the demo login refuses emails outside the allowlist', function () {
    config()->set('demo.quick_login', true);
    $outsider = User::factory()->create(['email' => 'someone-else@teamhub.test']);

    $response = $this->from(route('login'))->post(route('demo.login'), [
        'email' => $outsider->email,
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('the demo login is hidden when the feature is disabled', function () {
    config()->set('demo.quick_login', false);
    User::factory()->student()->create(['email' => 'staff@teamhub.test']);

    $response = $this->post(route('demo.login'), ['email' => 'staff@teamhub.test']);

    $this->assertGuest();
    $response->assertNotFound();
});

test('the role entry screen exposes the three demo roles', function () {
    config()->set('demo.quick_login', true);
    User::factory()->universityStaff()->create([
        'email' => 'admin@teamhub.test',
        'name' => 'Admin User',
    ]);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->component('app/Entry')
            ->has('demo.accounts', 3)
            ->where('demo.accounts.0.email', 'admin@teamhub.test')
            ->where('demo.accounts.0.role', 'admin')
            ->where('demo.accounts.0.label', 'مدير')
            ->where('demo.accounts.1.role', 'staff')
            ->where('demo.accounts.1.label', 'موظف')
            ->where('demo.accounts.2.role', 'project_leader')
            ->where('demo.accounts.2.label', 'قائد المشروع')
        );
});
