<?php

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;

test('demo entry is the home page when quick login is enabled', function () {
    config()->set('demo.quick_login', true);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('team-hub/Entry'));
});

test('login route redirects to role entry when quick login is enabled', function () {
    config()->set('demo.quick_login', true);

    $this->get(route('login'))
        ->assertRedirect(route('home'));
});

test('admin can create a project even when no clubs are seeded', function () {
    config()->set('demo.quick_login', true);

    $admin = User::factory()->universityStaff()->create(['email' => 'admin@teamhub.test']);

    expect(Workspace::query()->count())->toBe(0);

    $this->actingAs($admin)
        ->post(route('dashboard.projects.store'), [
            'name' => 'مشروع تجريبي',
        ])
        ->assertRedirect(route('dashboard'));

    expect(Workspace::query()->count())->toBeGreaterThan(0);
    expect(Project::query()->where('name', 'مشروع تجريبي')->exists())->toBeTrue();
});

test('demo personas receive dedicated dashboard panels', function () {
    config()->set('demo.quick_login', true);

    User::factory()->universityStaff()->create(['email' => 'admin@teamhub.test']);
    User::factory()->student()->create(['email' => 'project-lead@teamhub.test']);
    User::factory()->student()->create(['email' => 'staff@teamhub.test']);

    $this->post(route('demo.login'), ['email' => 'admin@teamhub.test'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('demoPersona', 'admin')
            ->where('dashboard.type', 'admin')
            ->has('dashboard.projects')
        );
});

test('hub dashboard returns real project and task props for a student', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $student = User::factory()->student()->create();

    ProjectMembership::factory()->create([
        'user_id' => $student->id,
        'project_id' => $project->id,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $student->id,
        'due_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team-hub/Dashboard')
            ->where('demoPersona', null)
            ->where('dashboard.type', 'legacy')
            ->has('dashboard.projects', 1)
            ->has('dashboard.kpis', 4)
            ->where('dashboard.roleContext.panel', 'member')
        );
});

test('hub projects supports search query', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $visible = Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Alpha Project']);
    $hidden = Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Beta Hidden']);
    $student = User::factory()->student()->create();

    foreach ([$visible, $hidden] as $project) {
        ProjectMembership::factory()->create([
            'user_id' => $student->id,
            'project_id' => $project->id,
        ]);
    }

    $this->actingAs($student)
        ->get(route('projects', ['q' => 'Alpha']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team-hub/Projects')
            ->has('projects.data', 1)
            ->where('projects.data.0.title', 'Alpha Project')
        );
});

test('hub tasks filters by status', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $student = User::factory()->student()->create();

    ProjectMembership::factory()->create([
        'user_id' => $student->id,
        'project_id' => $project->id,
    ]);

    Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);

    $this->actingAs($student)
        ->get(route('tasks', ['status' => 'done']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team-hub/Tasks')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.status', 'done')
        );
});

test('student home url points to dashboard', function () {
    $student = User::factory()->student()->create();

    expect($student->homeUrl())->toBe(route('dashboard', absolute: false));
});

test('shared app nav does not contain placeholder hash links', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('app.nav')
            ->where('app.nav', fn ($items) => collect($items)->every(
                fn (array $item) => $item['href'] !== '#'
            ))
        );
});
