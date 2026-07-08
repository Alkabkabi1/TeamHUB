<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;

test('guest is redirected from workspace show', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('login'));
});

test('non-member is redirected from workspace show to dashboard', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('dashboard'));
});

test('approved member is redirected from workspace show to dashboard', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('dashboard'));
});

test('pending applicant is redirected from workspace show to dashboard', function () {
    $user = User::factory()->create(['email' => 'pending@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembershipRequest::factory()->pending()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('dashboard'));
});
