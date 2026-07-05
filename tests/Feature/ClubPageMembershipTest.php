<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;

test('isMember is false for guest on club page', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', false)
        );
});

test('isMember is false for user with no membership', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', false)
        );
});

test('isMember is true for approved member', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', true)
        );
});

test('isMember is true for user with pending application', function () {
    $user = User::factory()->create(['email' => 'pending@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembershipRequest::factory()->pending()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('isMember', true)
        );
});
