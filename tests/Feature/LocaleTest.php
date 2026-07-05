<?php

use App\Models\User;
use App\Models\Workspace;

test('defaults to arabic when no locale cookie is present', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
        );
});

test('defaults to arabic for web crawlers regardless of accept-language header', function () {
    $this->get(route('home'), ['Accept-Language' => 'en-US,en;q=0.9'])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
        );
});

test('defaults to arabic even when app locale is configured as english', function () {
    config(['app.locale' => 'en']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
        );
});

test('locale can be switched to english via post', function () {
    $this->post(route('locale.update'), ['locale' => 'en']);
    $this->withUnencryptedCookie('locale', 'en');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('direction', 'ltr')
        );
});

test('locale can be switched to arabic via post', function () {
    $this->post(route('locale.update'), ['locale' => 'ar']);
    $this->withUnencryptedCookie('locale', 'ar');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
        );
});

test('invalid locale returns validation error', function () {
    $this->post(route('locale.update'), ['locale' => 'fr'])
        ->assertInvalid(['locale']);
});

test('english locale is reflected in inertia shared props', function () {
    $this->withUnencryptedCookie('locale', 'en');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('direction', 'ltr')
            ->where('translations.app.name', 'TeamHUB')
        );
});

test('arabic locale is reflected in inertia shared props', function () {
    $this->withUnencryptedCookie('locale', 'ar');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
            ->where('translations.app.name', 'TeamHUB')
        );
});

test('posting locale updates preference for subsequent requests', function () {
    $this->post(route('locale.update'), ['locale' => 'en']);
    $this->withUnencryptedCookie('locale', 'en');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('direction', 'ltr')
        );
});

test('join success flash uses english when locale is en', function () {
    $user = User::factory()->create(['email' => 'applicant@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->post(route('workspaces.join.store', $workspace), validJoinApplicationPayload($user))
        ->assertRedirect(route('workspaces.show', $workspace))
        ->assertInertiaFlash('toast.message', __('join.submitted', [], 'en'));
});
