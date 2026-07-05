<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function myTasksMemberContext(): array
{
    $user = User::factory()->student()->create();

    $workspaceA = Workspace::factory()->create(['status' => 'active']);
    $workspaceB = Workspace::factory()->create(['status' => 'active']);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspaceA->id,
    ]);
    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspaceB->id,
    ]);

    $projectA = Project::factory()->create(['workspace_id' => $workspaceA->id, 'name' => 'Project A']);
    $projectB = Project::factory()->create(['workspace_id' => $workspaceA->id, 'name' => 'Project B']);
    $projectC = Project::factory()->create(['workspace_id' => $workspaceB->id, 'name' => 'Project C']);

    foreach ([$projectA, $projectB, $projectC] as $project) {
        $membership = ProjectMembership::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
        $membership->syncProjectRoles([ProjectRole::Member]);
    }

    return [$user, $workspaceA, $workspaceB, $projectA, $projectB, $projectC];
}

test('a member with tasks in three projects sees one unified my tasks view', function () {
    [$user, $workspaceA, , $projectA, $projectB, $projectC] = myTasksMemberContext();

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
        'due_at' => now()->addHour(),
        'status' => 'in_progress',
    ]);
    Task::factory()->create([
        'project_id' => $projectC->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'title' => 'Upcoming task',
        'due_at' => now()->addDays(3),
        'status' => 'review',
    ]);
    Task::factory()->create([
        'project_id' => $projectA->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'title' => 'No due date task',
        'due_at' => null,
        'status' => 'todo',
    ]);
    Task::factory()->create([
        'project_id' => $projectA->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'title' => 'Completed task',
        'due_at' => now()->addDay(),
        'status' => 'done',
    ]);

    $this->actingAs($user)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('summary.overdue_count', 1)
            ->where('summary.due_today_count', 1)
            ->where('summary.upcoming_count', 1)
            ->where('summary.no_due_date_count', 1)
            ->where('summary.open_count', 4)
            ->has('overdueTasks', 1)
            ->has('dueTodayTasks', 1)
            ->has('upcomingTasks', 1)
            ->has('noDueDateTasks', 1)
            ->where('overdueTasks.0.committee.name', 'Project A')
            ->where('dueTodayTasks.0.committee.name', 'Project B')
            ->where('upcomingTasks.0.committee.name', 'Project C')
            ->where('noDueDateTasks.0.club.id', $workspaceA->id)
        );
});

test('my tasks quick status changes obey authorization and return to the my tasks page', function () {
    [$user, $workspaceA, , $projectA] = myTasksMemberContext();
    $otherUser = User::factory()->student()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $otherUser->id,
        'workspace_id' => $workspaceA->id,
    ]);

    $otherMembership = ProjectMembership::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $projectA->id,
    ]);
    $otherMembership->syncProjectRoles([ProjectRole::Member]);

    $ownTask = Task::factory()->create([
        'project_id' => $projectA->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'status' => 'todo',
    ]);

    $otherTask = Task::factory()->create([
        'project_id' => $projectA->id,
        'created_by' => $user->id,
        'assigned_to' => $otherUser->id,
        'status' => 'todo',
    ]);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$workspaceA, $projectA, $ownTask]), [
            'status' => 'in_progress',
            'return_to' => '/my-tasks',
        ])
        ->assertRedirect('/my-tasks');

    expect($ownTask->fresh()->status->value)->toBe('in_progress');

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$workspaceA, $projectA, $otherTask]), [
            'status' => 'in_progress',
            'return_to' => '/my-tasks',
        ])
        ->assertForbidden();
});
