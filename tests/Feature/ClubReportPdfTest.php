<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

test('guest is redirected when downloading club reports', function () {
    $workspace = Workspace::factory()->create();

    $this->get(route('workspaces.reports.members', ['workspace' => $workspace]))
        ->assertRedirect(route('login'));
});

test('student cannot download club reports', function () {
    $student = User::factory()->student()->create();
    $workspace = Workspace::factory()->create();

    $this->actingAs($student)
        ->get(route('workspaces.reports.members', ['workspace' => $workspace]))
        ->assertForbidden();
});

test('supervisor cannot download reports for another club', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $supervisedClub = Workspace::factory()->create(['status' => 'active']);
    $otherClub = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $supervisedClub->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('workspaces.reports.members', ['workspace' => $otherClub]))
        ->assertForbidden();
});

test('supervisor can download members pdf report for supervised club', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $workspace = Workspace::factory()->create(['status' => 'active', 'name' => 'نادي الحاسبات']);
    $member = User::factory()->student()->create(['name' => 'عضو تجريبي']);

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);

    $response = $this->actingAs($supervisor)
        ->get(route('workspaces.reports.members', ['workspace' => $workspace, 'locale' => 'ar']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
    expect(strlen($response->getContent() ?? ''))->toBeGreaterThan(100);
});

test('supervisor can download english locale report', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('workspaces.reports.members', ['workspace' => $workspace, 'locale' => 'en']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('invalid report locale returns validation error', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('workspaces.reports.members', ['workspace' => $workspace, 'locale' => 'fr']))
        ->assertInvalid(['locale']);
});
