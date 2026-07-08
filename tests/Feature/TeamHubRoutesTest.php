<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;

test('demo entry is the home page when quick login is enabled', function () {
    config()->set('demo.quick_login', true);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('app/Entry'));
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

test('members without management roles are redirected from dashboard to my tasks', function () {
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
        ->assertRedirect(route('my-tasks'));
});

test('legacy projects overview redirects to dashboard', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $visible = Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Alpha Project']);
    $student = User::factory()->student()->create();

    ProjectMembership::factory()->create([
        'user_id' => $student->id,
        'project_id' => $visible->id,
    ]);

    $this->actingAs($student)
        ->get(route('projects', ['q' => 'Alpha']))
        ->assertRedirect(route('dashboard', ['q' => 'Alpha']));
});

test('legacy tasks overview redirects members to my tasks', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $student = User::factory()->student()->create();

    ProjectMembership::factory()->create([
        'user_id' => $student->id,
        'project_id' => $project->id,
    ]);

    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);

    $this->actingAs($student)
        ->get(route('tasks', ['status' => 'done']))
        ->assertRedirect(route('my-tasks', ['status' => 'done']));
});

test('member home url points to my tasks', function () {
    $student = User::factory()->student()->create();

    expect($student->homeUrl())->toBe(route('my-tasks', absolute: false));
});

test('project lead home url points to dashboard', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    $membership = ProjectMembership::factory()->create([
        'user_id' => $lead->id,
        'project_id' => $project->id,
    ]);
    $membership->syncProjectRoles([ProjectRole::ProjectLead]);

    expect($lead->homeUrl())->toBe(route('dashboard', absolute: false));
});

test('shared app nav does not contain placeholder hash links', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('app.nav')
            ->where('app.nav', fn ($items) => collect($items)->every(
                fn (array $item) => $item['href'] !== '#'
            ))
        );
});

test('member nav focuses on my tasks instead of redundant overview links', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('app.nav.0.href', route('my-tasks', absolute: false))
            ->where('app.nav.0.label', __('dashboard.nav.my_tasks'))
        );
});
