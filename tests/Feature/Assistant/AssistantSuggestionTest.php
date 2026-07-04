<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests receive public starter suggestions', function () {
    app()->setLocale('en');

    $response = $this->getJson(route('assistant.suggestions'));

    $response->assertOk()
        ->assertJsonStructure(['suggestions']);

    expect($response->json('suggestions'))
        ->toBeArray()
        ->not->toBeEmpty()
        ->and(collect($response->json('suggestions'))->contains(
            fn (string $suggestion): bool => str_contains($suggestion, 'TeamHUB') || str_contains($suggestion, 'sign in'),
        ))->toBeTrue();
});

test('authenticated users receive personalised starter suggestions', function () {
    app()->setLocale('en');

    $user = User::factory()->student()->create();

    $response = $this->actingAs($user)->getJson(route('assistant.suggestions'));

    $response->assertOk();

    expect($response->json('suggestions'))
        ->toBeArray()
        ->not->toBeEmpty()
        ->and(collect($response->json('suggestions'))->contains(
            fn (string $suggestion): bool => str_contains($suggestion, 'task')
                || str_contains($suggestion, 'project')
                || str_contains($suggestion, 'مهام')
                || str_contains($suggestion, 'مشروع'),
        ))->toBeTrue();
});
