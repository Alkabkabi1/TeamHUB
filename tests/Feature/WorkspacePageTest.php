<?php

use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

test('club show page returns club data from database', function () {
    $workspace = Workspace::factory()->create([
        'name' => 'مساحة الحاسبات',
        'status' => 'active',
    ]);

    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    Task::factory()->count(2)->create(['project_id' => $project->id, 'status' => 'todo']);

    $this->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('WorkspacePage')
            ->where('workspace.name', 'مساحة الحاسبات')
            ->where('stats.members_count', 0)
            ->where('stats.projects_count', 1)
            ->where('stats.open_tasks_count', 2)
        );
});

test('club show page includes member count', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $member = User::factory()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.members_count', 1)
        );
});

test('club show page lists committee project updates', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);

    ProjectUpdate::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'title' => 'Project Update',
        'published_at' => now(),
    ]);

    ProjectUpdate::factory()->create(['title' => 'Other Club Post']);

    $this->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('WorkspacePage')
            ->has('recentUpdates', 1)
            ->where('recentUpdates.0.title', 'Project Update')
        );
});

test('club show page recent updates is empty when club has no project posts', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);

    $this->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('WorkspacePage')
            ->has('recentUpdates', 0)
        );
});
