<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A student who is an approved ClubLead of a freshly created club.
 *
 * @return array{0: User, 1: Club}
 */
function clubLeadAndClub(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $lead = User::factory()->student()->create();
    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    return [$lead, $workspace];
}

test('the workspace project listing and page render for authenticated users', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $user = User::factory()->student()->create();

    $this->actingAs($user)
        ->get(route('projects.index', $workspace))
        ->assertOk();
    $this->actingAs($user)
        ->get(route('projects.show', [$workspace, $project]))
        ->assertOk();
});

test('a committee from another club 404s under a mismatched club', function () {
    $workspaceA = Workspace::factory()->create(['status' => 'active']);
    $workspaceB = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspaceA->id]);

    $user = User::factory()->student()->create();

    $this->actingAs($user)
        ->get(route('projects.show', [$workspaceB, $project]))
        ->assertNotFound();
});

test('a club lead can create a committee and becomes its lead', function () {
    [$lead, $workspace] = clubLeadAndClub();

    $this->actingAs($lead)
        ->post(route('projects.store', $workspace), ['name' => 'اللجنة العلمية'])
        ->assertRedirect();

    $project = Project::query()->where('workspace_id', $workspace->id)->firstOrFail();

    expect($project->name)->toBe('اللجنة العلمية')
        ->and($lead->fresh()->canManageProject($project))->toBeTrue();
});

test('a plain student cannot create a committee', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('projects.store', $workspace), ['name' => 'لجنة'])
        ->assertForbidden();
});

test('the committee dashboard is forbidden to non-managers and visible to managers', function () {
    [$lead, $workspace] = clubLeadAndClub();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $outsider = User::factory()->student()->create();

    $this->actingAs($outsider)
        ->get(route('projects.manage', [$workspace, $project]))
        ->assertForbidden();

    $this->actingAs($lead)
        ->get(route('projects.manage', [$workspace, $project]))
        ->assertOk();
});

test('an approved club member can request to join a committee', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $student = User::factory()->student()->create();
    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $student->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($student)
        ->post(route('projects.join', [$workspace, $project]))
        ->assertRedirect();

    $this->assertDatabaseHas('project_memberships', [
        'project_id' => $project->id,
        'user_id' => $student->id,
        'status' => 'pending',
    ]);
});

test('a non-club-member cannot request to join a committee', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('projects.join', [$workspace, $project]))
        ->assertForbidden();
});

test('a committee manager can approve a pending join request', function () {
    [$lead, $workspace] = clubLeadAndClub();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $applicant = User::factory()->student()->create();
    $pending = ProjectMembership::factory()->pending()->create([
        'user_id' => $applicant->id,
        'project_id' => $project->id,
    ]);

    $this->actingAs($lead)
        ->post(route('projects.memberships.approve', [$workspace, $project, $pending]))
        ->assertRedirect();

    expect($pending->fresh()->status)->toBe('approved');
});

test('a committee-scoped news post is created with the committee id', function () {
    [$lead, $workspace] = clubLeadAndClub();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($lead)
        ->post(route('projects.updates.store', [$workspace, $project]), [
            'title' => 'خبر اللجنة',
            'body' => 'محتوى الخبر',
        ])
        ->assertRedirect(route('projects.updates.index', [$workspace, $project]));

    $this->assertDatabaseHas('project_updates', [
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'title' => 'خبر اللجنة',
    ]);
});

test('the last committee lead cannot be removed', function () {
    [$lead, $workspace] = clubLeadAndClub();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    // The lead becomes the committee's sole CommitteeLead.
    $membership = ProjectMembership::factory()->create([
        'user_id' => $lead->id,
        'project_id' => $project->id,
        'status' => 'approved',
    ]);
    $membership->syncProjectRoles([ProjectRole::ProjectLead, ProjectRole::Member]);

    $this->actingAs($lead)
        ->delete(route('projects.members.destroy', [$workspace, $project, $membership]))
        ->assertRedirect();

    $this->assertDatabaseHas('project_memberships', ['id' => $membership->id]);
});

test('archiving a committee soft-deletes it', function () {
    [$lead, $workspace] = clubLeadAndClub();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($lead)
        ->delete(route('projects.destroy', [$workspace, $project]))
        ->assertRedirect(route('projects.index', $workspace));

    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

test('a club lead can open committee settings with managed project switcher context', function () {
    [$lead, $workspace] = clubLeadAndClub();
    $projectA = Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Project Alpha']);
    $projectB = Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Project Beta']);

    $this->actingAs($lead)
        ->get(route('projects.edit', [$workspace, $projectA]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Form')
            ->where('mode', 'edit')
            ->where('committee.id', $projectA->id)
            ->has('auth.user.managed_projects', 2)
        );
});

test('a club lead can update committee settings', function () {
    [$lead, $workspace] = clubLeadAndClub();
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Legacy Project Name',
        'description' => 'Old description',
    ]);

    $this->actingAs($lead)
        ->put(route('projects.update', [$workspace, $project]), [
            'name' => 'Validation Project Name',
            'description' => 'Updated project settings for readiness validation.',
            'status' => 'active',
        ])
        ->assertRedirect(route('projects.manage', [$workspace, $project]));

    $project->refresh();

    expect($project->name)->toBe('Validation Project Name')
        ->and($project->description)->toBe('Updated project settings for readiness validation.');
});
