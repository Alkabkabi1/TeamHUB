<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function phase4MemberContext(): array
{
    $user = User::factory()->student()->create([
        'name' => 'Demo Member',
        'email' => 'phase4-member@example.com',
    ]);

    $workspaceA = Workspace::factory()->create(['name' => 'Workspace Alpha', 'status' => 'active']);
    $workspaceB = Workspace::factory()->create(['name' => 'Workspace Beta', 'status' => 'active']);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspaceA->id,
    ]);
    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspaceB->id,
    ]);

    $projectA = Project::factory()->create(['workspace_id' => $workspaceA->id, 'name' => 'Project One']);
    $projectB = Project::factory()->create(['workspace_id' => $workspaceA->id, 'name' => 'Project Two']);
    $projectC = Project::factory()->create(['workspace_id' => $workspaceB->id, 'name' => 'Project Three']);

    foreach ([$projectA, $projectB, $projectC] as $project) {
        $membership = ProjectMembership::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
        $membership->syncProjectRoles([ProjectRole::Member]);
    }

    return [$user, $workspaceA, $workspaceB, $projectA, $projectB, $projectC];
}

test('guest is redirected to login when visiting student dashboard', function () {
    $this->get(route('student-dashboard'))
        ->assertRedirect(route('login'));
});

test('student dashboard shows TeamHUB work summary and recent project activity', function () {
    [$user, $workspaceA, $workspaceB, $projectA, $projectB, $projectC] = phase4MemberContext();

    Task::factory()->create([
        'project_id' => $projectA->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'title' => 'Overdue task',
        'due_at' => now()->subDay(),
        'status' => 'todo',
    ]);

    Task::factory()->create([
        'project_id' => $projectB->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'title' => 'Due today task',
        'due_at' => now()->addHours(3),
        'status' => 'in_progress',
    ]);

    Task::factory()->create([
        'project_id' => $projectC->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'title' => 'Upcoming task',
        'due_at' => now()->addDays(2),
        'status' => 'review',
    ]);

    ProjectUpdate::factory()->create([
        'workspace_id' => $workspaceA->id,
        'project_id' => $projectA->id,
        'user_id' => $user->id,
        'title' => 'Project update',
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student-dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StudentDashboard')
            ->where('profile.name', 'Demo Member')
            ->where('profile.email', 'phase4-member@example.com')
            ->where('stats.workspacesCount', 2)
            ->where('stats.projectsCount', 3)
            ->where('stats.openTasksCount', 3)
            ->where('stats.dueTodayCount', 1)
            ->where('stats.overdueCount', 1)
            ->has('attentionTasks', 2)
            ->has('upcomingTasks', 1)
            ->has('recentUpdates', 1)
            ->where('recentUpdates.0.title', 'Project update')
            ->where('myTasksUrl', route('my-tasks', absolute: false))
        );
});
