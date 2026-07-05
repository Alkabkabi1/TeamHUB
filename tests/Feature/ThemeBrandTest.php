<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;

test('the app renders in light mode by default', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('class="dark"', false);
});

test('the app includes client-side appearance bootstrap logic', function () {
    $this
        ->get('/')
        ->assertOk()
        ->assertSee('window.localStorage.getItem(\'appearance\')', false)
        ->assertSee('document.documentElement.classList.toggle(', false)
        ->assertSee('resolvedAppearance === \'dark\'', false);
});

test('shared theme prop exposes the university default brand color', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('theme.brand', config('theme.brand'))
        );
});

test('club page overrides the brand color with the club theme', function () {
    $workspace = Workspace::factory()->create([
        'status' => 'active',
        'theme' => '#123456',
    ]);

    $this->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubPage')
            ->where('theme.brand', '#123456')
        );
});

test('club page falls back to the default brand when the club has no theme', function () {
    $workspace = Workspace::factory()->create([
        'status' => 'active',
        'theme' => null,
    ]);

    $this->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('theme.brand', config('theme.brand'))
        );
});

test('task pages inherit the project brand theme when present', function () {
    $workspace = Workspace::factory()->create([
        'status' => 'active',
        'theme' => '#123456',
    ]);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'theme' => '#654321',
    ]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);
    $supervisor = supervisorForClub($workspace);

    $this->actingAs($supervisor)
        ->get(route('projects.tasks.show', [$workspace, $project, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Show')
            ->where('theme.brand', '#654321')
            ->where('club.theme', '#123456')
            ->where('committee.theme', '#654321')
        );
});

test('task pages fall back to the workspace brand when the project has no theme', function () {
    $workspace = Workspace::factory()->create([
        'status' => 'active',
        'theme' => '#123456',
    ]);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'theme' => null,
    ]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);
    $supervisor = supervisorForClub($workspace);

    $this->actingAs($supervisor)
        ->get(route('projects.tasks.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Index')
            ->where('theme.brand', '#123456')
            ->where('club.theme', '#123456')
            ->where('committee.theme', null)
        );
});
