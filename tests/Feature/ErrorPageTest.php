<?php

use Illuminate\Support\Facades\Route;

test('a missing page renders the branded 404 error page', function () {
    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
        );
});

test('a forbidden response renders the branded 403 error page', function () {
    Route::middleware('web')->get('/__test/forbidden', fn () => abort(403));

    $this->get('/__test/forbidden')
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 403)
        );
});

test('the error page receives shared translations and direction', function () {
    $this->get('/this-route-does-not-exist')
        ->assertInertia(fn ($page) => $page
            ->where('direction', 'rtl')
            ->has('translations.errors.404.title')
        );
});
