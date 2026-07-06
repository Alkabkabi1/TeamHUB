<?php

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function workspaceLeadAndClub(): array
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

test('workspace overview exposes project cards and recent activity', function () {
    [$lead, $workspace] = workspaceLeadAndClub();
    $project = Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Website Refresh']);

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'title' => 'Ship landing page',
        'due_at' => now()->subDay(),
        'status' => 'in_progress',
    ]);

    ProjectUpdate::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $lead->id,
        'title' => 'Workspace kickoff update',
        'published_at' => now(),
    ]);

    $this->actingAs($lead)
        ->get(route('workspaces.manage', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Manage')
            ->where('workspaceStats.projects_count', 1)
            ->where('workspaceStats.tasks_count', 1)
            ->where('workspaceStats.overdue_tasks_count', 1)
            ->has('workspaceProjects', 1)
            ->where('workspaceProjects.0.name', 'Website Refresh')
            ->has('recentActivity')
        );
});

test('workspace members page loads for club leads', function () {
    [$lead, $workspace] = workspaceLeadAndClub();

    $this->actingAs($lead)
        ->get(route('workspaces.manage.members', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('workspaces/Members')
            ->where('workspace.id', $workspace->id)
            ->has('members')
            ->has('pendingApplications')
        );
});
