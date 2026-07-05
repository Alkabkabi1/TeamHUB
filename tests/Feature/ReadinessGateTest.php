<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\Mail;

function readinessLeadAndProject(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active', 'name' => 'Validation Workspace']);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Validation Project',
    ]);
    $lead = User::factory()->student()->create(['name' => 'Project Lead']);

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

function readinessMember(Workspace $workspace, Project $project, string $name = 'Project Member'): User
{
    $user = User::factory()->student()->create(['name' => $name]);

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $membership = ProjectMembership::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
    ]);
    $membership->syncProjectRoles([ProjectRole::Member]);

    return $user;
}

test('the core teamhub workflow stays consistent from task assignment to approval', function () {
    Mail::fake();

    [$lead, $workspace, $project] = readinessLeadAndProject();
    $member = readinessMember($workspace, $project);

    $this->actingAs($lead)
        ->get(route('projects.manage', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Manage')
            ->where('committee.id', $project->id)
        );

    $this->actingAs($lead)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Ship the validation phase',
            'description' => 'Prove the core workflow before Phase 6.',
            'assigned_to' => $member->id,
            'priority' => 'high',
            'status' => 'todo',
            'due_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect();

    $task = Task::query()->where('project_id', $project->id)->latest()->firstOrFail();

    $this->actingAs($member)
        ->get(route('projects.tasks.show', [$workspace, $project, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Show')
            ->where('task.id', $task->id)
            ->where('canComment', true)
            ->has('comments', 0)
            ->has('activities', 2)
        );

    $this->actingAs($member)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->where('auth.user.unread_notifications_count', 1)
            ->has('unreadNotifications', 1)
        );

    $this->actingAs($member)
        ->patch(route('projects.tasks.update', [$workspace, $project, $task]), [
            'status' => 'in_progress',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $this->actingAs($member)
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'First draft is ready for review.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $this->actingAs($member)
        ->post(route('projects.tasks.deliverable', [$workspace, $project, $task]), [
            'deliverable_url' => 'https://example.com/validation-draft',
            'deliverable_notes' => 'Draft uploaded for review.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $this->actingAs($lead)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->has('unreadNotifications', 1)
        );

    $this->actingAs($lead)
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'Reviewing now. Thanks for the quick turnaround.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $this->actingAs($lead)
        ->post(route('projects.tasks.approve', [$workspace, $project, $task]), [
            'review_notes' => 'Approved for release.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('done')
        ->and(
            TaskActivity::query()
                ->where('task_id', $task->id)
                ->orderBy('id')
                ->pluck('type')
                ->map(fn ($type) => $type->value)
                ->all()
        )->toBe([
            'task.created',
            'task.assigned',
            'task.status_changed',
            'comment.added',
            'task.deliverable_submitted',
            'comment.added',
            'task.deliverable_approved',
        ]);

    $this->actingAs($member)
        ->get(route('projects.tasks.show', [$workspace, $project, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Show')
            ->where('task.status', 'done')
            ->has('comments', 2)
            ->has('activities', 7)
        );

    $this->actingAs($member)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->where('auth.user.unread_notifications_count', 2)
            ->has('unreadNotifications', 2)
        );
});

test('member work surfaces stay aligned with the approved workflow outcome', function () {
    Mail::fake();

    [$lead, $workspace, $project] = readinessLeadAndProject();
    $member = readinessMember($workspace, $project, 'Dashboard Member');

    $this->actingAs($lead)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Keep my work aligned',
            'assigned_to' => $member->id,
            'priority' => 'medium',
            'status' => 'todo',
            'due_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect();

    $task = Task::query()->where('project_id', $project->id)->latest()->firstOrFail();

    $this->actingAs($member)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('summary.open_count', 1)
        );

    $this->actingAs($member)
        ->post(route('projects.tasks.deliverable', [$workspace, $project, $task]), [
            'deliverable_url' => 'https://example.com/final',
            'deliverable_notes' => 'Ready.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $this->actingAs($lead)
        ->post(route('projects.tasks.approve', [$workspace, $project, $task]), [
            'review_notes' => 'Done.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $this->actingAs($member)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('summary.open_count', 0)
        );
});
