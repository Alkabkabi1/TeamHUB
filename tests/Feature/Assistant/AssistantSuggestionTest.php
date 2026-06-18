<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests receive public starter suggestions', function () {
    $response = $this->getJson(route('assistant.suggestions'));

    $response->assertOk()
        ->assertJsonStructure(['suggestions']);

    expect($response->json('suggestions'))
        ->toBeArray()
        ->not->toBeEmpty();
});

test('authenticated users receive personalised starter suggestions', function () {
    $user = User::factory()->student()->create();

    $response = $this->actingAs($user)->getJson(route('assistant.suggestions'));

    $response->assertOk();

    expect($response->json('suggestions'))
        ->toBeArray()
        ->not->toBeEmpty();
});
