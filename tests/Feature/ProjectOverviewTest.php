<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function projectOverviewLeadAndCommittee(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    $workspaceMembership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $workspaceMembership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    $projectMembership = ProjectMembership::factory()->create([
        'user_id' => $lead->id,
        'project_id' => $project->id,
    ]);
    $projectMembership->syncProjectRoles([ProjectRole::ProjectLead, ProjectRole::Member]);

    return [$lead, $workspace, $project];
}

test('project overview exposes task stats and recent updates props', function () {
    [$lead, $workspace, $project] = projectOverviewLeadAndCommittee();

    $todoTask = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'status' => 'todo',
    ]);
    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'status' => 'review',
        'due_at' => now()->subDay(),
    ]);

    ProjectUpdate::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'user_id' => $lead->id,
        'title' => 'First sprint update',
        'published_at' => now(),
    ]);

    $todoTask->recordCreated($lead);

    $this->actingAs($lead)
        ->get(route('projects.manage', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/Manage')
            ->where('taskStats.todo', 1)
            ->where('taskStats.review', 1)
            ->where('taskStats.overdue', 1)
            ->has('recentUpdates', 1)
            ->where('recentUpdates.0.title', 'First sprint update')
            ->has('recentActivities', 1)
            ->where('recentActivities.0.task_title', $todoTask->title)
        );
});
