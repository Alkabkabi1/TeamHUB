<?php

use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Notifications\AdminMessageNotification;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Notification;

test('demo workflow completes from project creation through approval', function () {
    config()->set('demo.quick_login', true);
    Notification::fake();

    $admin = User::factory()->universityStaff()->create(['email' => 'admin@teamhub.test']);
    $leader = User::factory()->student()->create(['email' => 'project-lead@teamhub.test']);
    $staff = User::factory()->student()->create(['email' => 'staff@teamhub.test']);

    $workspace = Workspace::factory()->create(['status' => 'active']);
    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $leader->id,
        'workspace_id' => $workspace->id,
    ]);
    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $staff->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.projects.store'), [
            'name' => 'مشروع التدفق الكامل',
            'workspace_id' => $workspace->id,
            'leader_id' => $leader->id,
        ])
        ->assertRedirect(route('dashboard'));

    $project = Project::query()->where('name', 'مشروع التدفق الكامل')->first();
    expect($project)->not->toBeNull();

    $staffMembership = ProjectMembership::query()
        ->where('project_id', $project->id)
        ->where('user_id', $staff->id)
        ->first();
    expect($staffMembership)->not->toBeNull()
        ->and($staffMembership->status)->toBe('approved');

    $leaderMembership = ProjectMembership::query()
        ->where('project_id', $project->id)
        ->where('user_id', $leader->id)
        ->first();
    expect($leaderMembership)->not->toBeNull();
    expect($leaderMembership->projectRoles()->contains(ProjectRole::ProjectLead))->toBeTrue();

    $this->actingAs($leader)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('demoPersona', 'project_leader')
            ->where('dashboard.type', 'project_leader')
            ->where('dashboard.project.title', 'مشروع التدفق الكامل')
        );

    $this->actingAs($leader)
        ->post(route('dashboard.tasks.store'), [
            'project_id' => $project->id,
            'title' => 'مهمة للموظف',
            'assigned_to' => $staff->id,
            'priority' => 'medium',
        ])
        ->assertRedirect(route('dashboard'));

    $task = Task::query()
        ->where('project_id', $project->id)
        ->where('title', 'مهمة للموظف')
        ->first();

    expect($task)->not->toBeNull()
        ->and($task->assigned_to)->toBe($staff->id);

    Notification::assertSentTo($staff, TaskAssignedNotification::class);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('demoPersona', 'staff')
            ->where('dashboard.type', 'staff')
            ->where('dashboard.tasks.0.title', 'مهمة للموظف')
        );

    $this->actingAs($staff)
        ->post(route('tasks.deliverable', $task), [
            'deliverable_url' => 'https://example.com/deliverable',
            'deliverable_notes' => 'جاهز للمراجعة',
        ])
        ->assertRedirect(route('dashboard'));

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Review);

    $this->actingAs($leader)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dashboard.review_queue.0.title', 'مهمة للموظف')
        );

    $this->actingAs($leader)
        ->post(route('tasks.approve', $task), [
            'review_notes' => 'عمل ممتاز',
        ])
        ->assertRedirect(route('dashboard'));

    expect($task->fresh()->status)->toBe(TaskStatus::Done);
});

test('message leader sends an in-app notification', function () {
    config()->set('demo.quick_login', true);
    Notification::fake();

    $admin = User::factory()->universityStaff()->create(['email' => 'admin@teamhub.test']);
    $leader = User::factory()->student()->create(['email' => 'project-lead@teamhub.test']);

    $this->actingAs($admin)
        ->post(route('dashboard.message-leader'), [
            'leader_id' => $leader->id,
            'message' => 'Please review the new project plan.',
        ])
        ->assertRedirect(route('dashboard'));

    Notification::assertSentTo($leader, AdminMessageNotification::class);
});

test('hub mutations work for authorized production users when demo quick login is disabled', function () {
    config()->set('demo.quick_login', false);
    Notification::fake();

    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();
    $member = User::factory()->student()->create();

    $leadMembership = ProjectMembership::factory()->create([
        'user_id' => $lead->id,
        'project_id' => $project->id,
    ]);
    $leadMembership->syncProjectRoles([ProjectRole::ProjectLead, ProjectRole::Member]);

    ProjectMembership::factory()->create([
        'user_id' => $member->id,
        'project_id' => $project->id,
    ]);

    $this->actingAs($lead)
        ->post(route('dashboard.tasks.store'), [
            'project_id' => $project->id,
            'title' => 'Production task',
            'assigned_to' => $member->id,
        ])
        ->assertRedirect(route('dashboard'));

    Notification::assertSentTo($member, TaskAssignedNotification::class);
});

test('hub projects and tasks support pagination metadata', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    foreach (range(1, 25) as $index) {
        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => "Pagination Project {$index}",
            'description' => null,
            'status' => 'active',
        ]);

        ProjectMembership::factory()->create([
            'user_id' => $student->id,
            'project_id' => $project->id,
        ]);

        Task::factory()->create(['project_id' => $project->id]);
    }

    $this->actingAs($student)
        ->get(route('projects'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 20)
            ->where('projects.total', 25)
            ->where('projects.last_page', 2)
        );

    $this->actingAs($student)
        ->get(route('tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 20)
            ->where('tasks.total', 25)
        );
});

test('hub task show page renders for assigned staff', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $staff = User::factory()->student()->create();

    ProjectMembership::factory()->create([
        'user_id' => $staff->id,
        'project_id' => $project->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $staff->id,
        'title' => 'Hub task detail',
    ]);

    $this->actingAs($staff)
        ->get(route('tasks.show', $task))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team-hub/TaskShow')
            ->where('task.title', 'Hub task detail')
            ->where('canSubmitDeliverable', true)
        );
});
