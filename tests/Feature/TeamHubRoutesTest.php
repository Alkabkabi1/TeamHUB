<?php

use App\Models\Club;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\User;

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

    expect(Club::query()->count())->toBe(0);

    $this->actingAs($admin)
        ->post(route('hub.admin.projects.store'), [
            'name' => 'مشروع تجريبي',
        ])
        ->assertRedirect(route('hub.dashboard'));

    expect(Club::query()->count())->toBeGreaterThan(0);
    expect(Committee::query()->where('name', 'مشروع تجريبي')->exists())->toBeTrue();
});

test('demo personas receive dedicated dashboard panels', function () {
    config()->set('demo.quick_login', true);

    User::factory()->universityStaff()->create(['email' => 'admin@teamhub.test']);
    User::factory()->student()->create(['email' => 'project-leader@teamhub.test']);
    User::factory()->student()->create(['email' => 'staff@teamhub.test']);

    $this->post(route('demo.login'), ['email' => 'admin@teamhub.test'])
        ->assertRedirect(route('hub.dashboard', absolute: false));

    $this->get(route('hub.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('demoPersona', 'admin')
            ->where('dashboard.type', 'admin')
            ->has('dashboard.projects')
        );
});

test('hub dashboard returns real project and task props for a student', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $student = User::factory()->student()->create();

    CommitteeMembership::factory()->create([
        'user_id' => $student->id,
        'committee_id' => $committee->id,
    ]);

    Task::factory()->create([
        'committee_id' => $committee->id,
        'assigned_to' => $student->id,
        'due_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('hub.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team-hub/Dashboard')
            ->where('demoPersona', null)
            ->where('dashboard.type', 'legacy')
            ->has('dashboard.projects', 1)
            ->has('dashboard.kpis', 4)
            ->where('dashboard.roleContext.panel', 'student')
        );
});

test('hub projects supports search query', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $visible = Committee::factory()->create(['club_id' => $club->id, 'name' => 'Alpha Project']);
    $hidden = Committee::factory()->create(['club_id' => $club->id, 'name' => 'Beta Hidden']);
    $student = User::factory()->student()->create();

    foreach ([$visible, $hidden] as $committee) {
        CommitteeMembership::factory()->create([
            'user_id' => $student->id,
            'committee_id' => $committee->id,
        ]);
    }

    $this->actingAs($student)
        ->get(route('hub.projects', ['q' => 'Alpha']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team-hub/Projects')
            ->has('projects', 1)
            ->where('projects.0.title', 'Alpha Project')
        );
});

test('hub tasks filters by status', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $student = User::factory()->student()->create();

    CommitteeMembership::factory()->create([
        'user_id' => $student->id,
        'committee_id' => $committee->id,
    ]);

    Task::factory()->create(['committee_id' => $committee->id, 'status' => 'todo']);
    Task::factory()->create(['committee_id' => $committee->id, 'status' => 'done']);

    $this->actingAs($student)
        ->get(route('hub.tasks', ['status' => 'done']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team-hub/Tasks')
            ->has('tasks', 1)
            ->where('tasks.0.status', 'done')
        );
});

test('preview team hub routes redirect to authenticated hub', function () {
    $this->get('/preview/team-hub/dashboard')
        ->assertRedirect('/hub/dashboard');
});

test('student home url points to hub dashboard', function () {
    $student = User::factory()->student()->create();

    expect($student->homeUrl())->toBe(route('hub.dashboard', absolute: false));
});

test('shared hub nav does not contain placeholder hash links', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('hub.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('hub.nav')
            ->where('hub.nav', fn ($items) => collect($items)->every(
                fn (array $item) => $item['href'] !== '#'
            ))
        );
});
