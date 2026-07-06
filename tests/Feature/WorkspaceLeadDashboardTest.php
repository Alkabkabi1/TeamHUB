<?php

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;

/**
 * Create a club managed by a fresh club-lead supervisor and return both.
 *
 * @return array{0: User, 1: Club}
 */
function managedWorkspace(): array
{
    $supervisor = User::factory()->workspaceSupervisor()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    return [$supervisor, $workspace];
}

test('guest is redirected when visiting the club management dashboard', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->get(route('workspaces.manage', $workspace))
        ->assertRedirect(route('login'));
});

test('a student with no role in the club cannot access its management dashboard', function () {
    [, $workspace] = managedWorkspace();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('workspaces.manage', $workspace))
        ->assertForbidden();
});

test('a plain member cannot access the management dashboard', function () {
    [, $workspace] = managedWorkspace();
    $member = User::factory()->student()->create();
    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($member)
        ->get(route('workspaces.manage', $workspace))
        ->assertForbidden();
});

test('a manager of one club cannot manage a different club', function () {
    [$supervisor] = managedWorkspace();
    $otherClub = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($supervisor)
        ->get(route('workspaces.manage', $otherClub))
        ->assertForbidden();
});

test('university staff may manage any club', function () {
    $staff = User::factory()->universityStaff()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->actingAs($staff)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            // Staff bypass grants the full capability set.
            ->has('capabilities', count(WorkspaceCapability::cases()))
            ->where('canManageRoles', true)
        );
});

test('the management dashboard returns a valid inertia page for a club lead', function () {
    [$supervisor, $workspace] = managedWorkspace();

    $this->actingAs($supervisor)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            ->has('workspace')
            ->has('capabilities')
            ->where('canManageRoles', true)
            ->has('roleOptions')
            ->has('workspaceProjects')
            ->has('recentActivity')
            ->has('stats')
            ->has('members')
        );
});

test('management dashboard lists members including managers', function () {
    [$supervisor, $workspace] = managedWorkspace();
    $member = User::factory()->student()->create(['name' => 'Member One']);
    $secondMember = User::factory()->student()->create(['name' => 'Member Two']);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $secondMember->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            ->where('stats.membersCount', 3)
            ->has('members', 3)
            ->where('members', fn ($members) => collect($members)->contains(
                fn ($row) => $row['email'] === $member->email
            ))
            ->where('members', fn ($members) => collect($members)->contains(
                fn ($row) => $row['email'] === $supervisor->email && in_array('workspace_lead', $row['roles'], true)
            ))
        );
});

test('pendingApplicationsCount counts ClubJoinApplication rows not ClubMembership rows', function () {
    [$supervisor, $workspace] = managedWorkspace();

    WorkspaceMembershipRequest::factory()->pending()->count(2)->create([
        'workspace_id' => $workspace->id,
    ]);

    WorkspaceMembershipRequest::factory()->approved()->create([
        'workspace_id' => $workspace->id,
    ]);

    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => 'pending',
    ]);

    $this->actingAs($supervisor)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            ->where('stats.pendingApplicationsCount', 2)
        );
});

test('dashboard includes workspace projects for the club', function () {
    [$supervisor, $workspace] = managedWorkspace();

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Demo Project',
    ]);

    $this->actingAs($supervisor)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            ->has('workspaceProjects', 1)
            ->where('workspaceProjects.0.id', $project->id)
            ->where('stats.projectsCount', 1)
        );
});

test('recent activity includes committee posts', function () {
    [$supervisor, $workspace] = managedWorkspace();
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    $post = ProjectUpdate::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'title' => 'Project Update',
        'published_at' => now()->subDay(),
    ]);

    ProjectUpdate::factory()->create(['title' => 'Other Club Post']);

    $this->actingAs($supervisor)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            ->where('recentActivity', fn ($items) => collect($items)->contains(
                fn ($item) => $item['type'] === 'update' && $item['title'] === $post->title
            ))
        );
});

test('a membership manager only sees member-management capabilities on the dashboard', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $membershipManager = User::factory()->student()->create();
    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $membershipManager->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::MembershipManager]);

    $this->actingAs($membershipManager)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            ->where('capabilities', ['manage-members', 'view-reports'])
            ->where('canManageRoles', false)
        );
});
