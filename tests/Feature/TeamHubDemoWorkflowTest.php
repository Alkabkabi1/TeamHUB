<?php

use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;
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
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'مهمة للموظف',
            'assigned_to' => $staff->id,
            'priority' => 'medium',
        ])
        ->assertRedirect();

    $task = Task::query()
        ->where('project_id', $project->id)
        ->where('title', 'مهمة للموظف')
        ->first();

    expect($task)->not->toBeNull()
        ->and($task->assigned_to)->toBe($staff->id);

    Notification::assertSentTo($staff, TaskAssignedNotification::class);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertRedirect(route('my-tasks'));

    $this->actingAs($staff)
        ->post(route('projects.tasks.deliverable', [$workspace, $project, $task]), [
            'deliverable_url' => 'https://example.com/deliverable',
            'deliverable_notes' => 'جاهز للمراجعة',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Review);

    $this->actingAs($leader)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dashboard.review_queue.0.title', 'مهمة للموظف')
        );

    $this->actingAs($leader)
        ->post(route('projects.tasks.approve', [$workspace, $project, $task]), [
            'review_notes' => 'عمل ممتاز',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

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

test('admin can assign a workspace lead from the dashboard', function () {
    config()->set('demo.quick_login', true);

    $admin = User::factory()->universityStaff()->create(['email' => 'admin@teamhub.test']);
    $workspaceLead = User::factory()->student()->create(['email' => 'workspace-lead@teamhub.test']);
    $workspace = Workspace::factory()->create(['status' => 'active', 'name' => 'مساحة الحاسبات']);

    $this->actingAs($admin)
        ->post(route('dashboard.assign-workspace-leader'), [
            'workspace_id' => $workspace->id,
            'leader_id' => $workspaceLead->id,
        ])
        ->assertRedirect(route('dashboard'));

    $membership = WorkspaceMembership::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $workspaceLead->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->status)->toBe('approved')
        ->and($membership->hasWorkspaceRole(WorkspaceRole::WorkspaceLead))->toBeTrue();
});

test('message workspace leader sends an in-app notification', function () {
    config()->set('demo.quick_login', true);
    Notification::fake();

    $admin = User::factory()->universityStaff()->create(['email' => 'admin@teamhub.test']);
    $leader = User::factory()->student()->create(['email' => 'workspace-lead@teamhub.test']);

    $this->actingAs($admin)
        ->post(route('dashboard.message-workspace-leader'), [
            'leader_id' => $leader->id,
            'message' => 'Please review pending join requests.',
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
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Production task',
            'assigned_to' => $member->id,
        ])
        ->assertRedirect();

    Notification::assertSentTo($member, TaskAssignedNotification::class);
});

test('legacy hub overview routes redirect to role homes', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    foreach (range(1, 3) as $index) {
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
        ->assertRedirect(route('dashboard'));

    $this->actingAs($student)
        ->get(route('tasks'))
        ->assertRedirect(route('my-tasks'));
});

test('legacy task show redirects to canonical task page', function () {
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
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));
});
