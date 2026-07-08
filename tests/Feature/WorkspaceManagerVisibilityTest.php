<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Notifications\NewPostNotification;
use App\Services\WorkspaceMemberReportService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('a manager is excluded from the members report', function () {
    $workspace = Workspace::factory()->create();

    // A regular member.
    $member = User::factory()->create();
    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);

    // A manager promoted via the role pivot.
    $manager = User::factory()->create();
    $managerMembership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $manager->id,
        'workspace_id' => $workspace->id,
    ]);
    $managerMembership->assignWorkspaceRole(WorkspaceRole::WorkspaceLead);

    $members = app(WorkspaceMemberReportService::class)->membersForWorkspace($workspace);
    $emails = collect($members)->pluck('email');

    expect($emails)->toContain($member->email)
        ->and($emails)->not->toContain($manager->email);
});

test('committee managers are not notified of new project updates', function () {
    Notification::fake();

    $workspace = Workspace::factory()->create();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $lead = User::factory()->create();
    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);

    $member = User::factory()->create();
    ProjectMembership::factory()->create([
        'user_id' => $member->id,
        'project_id' => $project->id,
    ]);

    $manager = User::factory()->create();
    $managerMembership = ProjectMembership::factory()->create([
        'user_id' => $manager->id,
        'project_id' => $project->id,
    ]);
    $managerMembership->syncProjectRoles([ProjectRole::ContentManager]);

    $poster = User::factory()->create();
    ProjectMembership::factory()->create([
        'user_id' => $poster->id,
        'project_id' => $project->id,
    ])->syncProjectRoles([ProjectRole::ProjectLead]);

    $this->actingAs($poster)
        ->post(route('projects.updates.store', [$workspace, $project]), [
            'title' => 'مرحبا',
            'body' => 'نص التحديث هنا للمشروع',
        ])
        ->assertRedirect();

    Notification::assertSentTo($member, NewPostNotification::class);
    Notification::assertNotSentTo($manager, NewPostNotification::class);
    Notification::assertNotSentTo($lead, NewPostNotification::class);
});

test('managedClub returns a fully hydrated club for theme and logo', function () {
    Storage::fake('public');

    $supervisor = User::factory()->create();
    $workspace = Workspace::factory()->withTheme('#123456')->withLogo()->create();
    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    $managed = $supervisor->managedWorkspace();

    expect($managed)->not->toBeNull()
        ->and($managed->theme)->toBe('#123456')
        ->and($managed->logo_url)->not->toBeNull();
});
