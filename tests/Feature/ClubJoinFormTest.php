<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;

test('guest is redirected to login when visiting join form', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->get(route('workspaces.join.create', $workspace))
        ->assertRedirect(route('login'));
});

test('authenticated user can view join form with club props', function () {
    $user = User::factory()->create(['email' => 'applicant@teamhub.test']);
    $workspace = Workspace::factory()->create(['name' => 'مساحة الحاسبات', 'status' => 'active']);

    $this->actingAs($user)
        ->get(route('workspaces.join.create', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubJoinForm')
            ->where('club.name', 'مساحة الحاسبات')
            ->where('defaults.full_name', $user->name)
        );
});

test('join form returns not found for inactive club', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('workspaces.join.create', $workspace))
        ->assertNotFound();
});

test('authenticated user can submit join application', function () {
    $user = User::factory()->create([
        'name' => 'وئام راشد',
        'email' => 'applicant@teamhub.test',
    ]);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('workspaces.join.store', $workspace), validJoinApplicationPayload($user))
        ->assertRedirect(route('workspaces.show', $workspace));

    $application = WorkspaceMembershipRequest::query()
        ->where('user_id', $user->id)
        ->where('workspace_id', $workspace->id)
        ->first();

    expect($application)->not->toBeNull()
        ->and($application->status)->toBe('pending')
        ->and($application->weekly_hours)->toBe(4);
});

test('join application validates required fields', function () {
    $user = User::factory()->create(['email' => 'student@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('workspaces.join.store', $workspace), [])
        ->assertSessionHasErrors([
            'full_name',
            'phone',
            'skills',
            'weekly_hours',
            'tools',
            'motivation',
            'contribution',
        ]);
});

test('duplicate pending application is rejected', function () {
    $user = User::factory()->create(['email' => 'dup@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembershipRequest::factory()->pending()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->post(route('workspaces.join.store', $workspace), validJoinApplicationPayload($user))
        ->assertSessionHasErrors('club');
});

test('existing club membership blocks new application', function () {
    $user = User::factory()->create(['email' => 'member@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($user)
        ->post(route('workspaces.join.store', $workspace), validJoinApplicationPayload($user))
        ->assertSessionHasErrors('club');
});

test('join application rejected for inactive club', function () {
    $user = User::factory()->create(['email' => 'student@teamhub.test']);
    $workspace = Workspace::factory()->inactive()->create();

    $this->actingAs($user)
        ->post(route('workspaces.join.store', $workspace), validJoinApplicationPayload($user))
        ->assertSessionHasErrors('club');
});

test('admins get 403 when posting a join application', function () {
    $user = User::factory()->admin()->create(['email' => 'staff@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->post(route('workspaces.join.store', $workspace), validJoinApplicationPayload($user))
        ->assertForbidden();
});
