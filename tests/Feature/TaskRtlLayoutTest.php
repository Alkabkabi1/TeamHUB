<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function rtlTaskLeadAndProject(): array
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

test('task list renders with arabic rtl shared props', function () {
    [$lead, $workspace, $project] = rtlTaskLeadAndProject();

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'title' => 'مهمة تجريبية',
    ]);

    $this->actingAs($lead)
        ->withUnencryptedCookie('locale', 'ar')
        ->get(route('projects.tasks.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/tasks/Index')
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
            ->has('tasks', 1)
            ->where('tasks.0.title', 'مهمة تجريبية')
            ->where('statusOptions.0.label', __('tasks.statuses.todo', [], 'ar'))
        );
});

test('task detail renders with arabic rtl shared props and collaboration surface', function () {
    [$lead, $workspace, $project] = rtlTaskLeadAndProject();
    $member = User::factory()->student()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'workspace_id' => $workspace->id,
    ]);

    $membership = ProjectMembership::factory()->create([
        'user_id' => $member->id,
        'project_id' => $project->id,
    ]);
    $membership->syncProjectRoles([ProjectRole::Member]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'title' => 'مراجعة التسليم',
        'status' => 'in_progress',
    ]);

    $task->addComment($member, 'التعليق الأول جاهز للمراجعة.');

    $this->actingAs($member)
        ->withUnencryptedCookie('locale', 'ar')
        ->get(route('projects.tasks.show', [$workspace, $project, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/tasks/Show')
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
            ->where('task.title', 'مراجعة التسليم')
            ->where('canComment', true)
            ->has('comments', 1)
            ->where('comments.0.body', 'التعليق الأول جاهز للمراجعة.')
        );
});

test('my tasks page renders with arabic rtl shared props', function () {
    [$lead, $workspace, $project] = rtlTaskLeadAndProject();

    Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $lead->id,
        'status' => 'todo',
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($lead)
        ->withUnencryptedCookie('locale', 'ar')
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
            ->where('summary.open_count', 1)
        );
});
