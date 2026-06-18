<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('login captures the public page a guest came from and flashes a notice', function () {
    $response = $this->withHeader('referer', url('/events/1'))->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->hasFlash('toast.message', __('auth.login_required')));
    expect(session('url.intended'))->toBe('/events/1');
});

test('login returns the user to the page they came from after authenticating', function () {
    $user = User::factory()->student()->create();

    $this->withHeader('referer', url('/events/1'))->get(route('login'));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/events/1');
});

test('login ignores an off-site return target', function () {
    $response = $this->withHeader('referer', 'https://evil.test/phishing')->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->missingFlash('toast'));
    expect(session('url.intended'))->toBeNull();
});

test('login does not override the intended url set by the auth middleware', function () {
    $this->withSession(['url.intended' => '/clubs/3'])
        ->withHeader('referer', url('/events/1'))
        ->get(route('login'))
        ->assertOk();

    expect(session('url.intended'))->toBe('/clubs/3');
});

test('registration returns the user to the page they came from', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->withHeader('referer', url('/events/1'))->get(route('register'));

    expect(session('url.intended'))->toBe('/events/1');

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@uqu.edu.sa',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/events/1');
});

test('logging out clears a stale intended url', function () {
    $user = User::factory()->student()->create();

    $this->actingAs($user)
        ->withSession(['url.intended' => '/clubs/3'])
        ->post(route('logout'));

    expect(session('url.intended'))->toBeNull();
});

test('login after logout does not recapture the referer page', function () {
    $user = User::factory()->student()->create();

    // Logout sets the skip flag, then the login screen is loaded with the
    // logged-out page still sitting in the referer header.
    $this->actingAs($user)->post(route('logout'));

    $this->withHeader('referer', url('/clubs/3'))
        ->get(route('login'))
        ->assertOk();

    expect(session('url.intended'))->toBeNull();
});

test('visiting login directly without a referer keeps the default redirect', function () {
    $user = User::factory()->student()->create();

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->missingFlash('toast'));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('student-dashboard', absolute: false));
});
