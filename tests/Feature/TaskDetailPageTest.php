<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function taskDetailLeadAndProject(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active', 'theme' => '#112233']);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'theme' => '#445566',
    ]);
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

function taskDetailMember(Workspace $workspace, Project $project): User
{
    $user = User::factory()->student()->create();

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

test('task detail page renders comments, activities, and collaboration flags', function () {
    [$lead, $workspace, $project] = taskDetailLeadAndProject();
    $member = taskDetailMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'in_progress',
        'title' => 'Validate the collaboration surface',
    ]);

    $task->recordCreated($lead);
    $task->recordAssignment($lead, null, $member);
    $task->addComment($member, 'The main draft is ready.');

    $this->actingAs($lead)
        ->get(route('projects.tasks.show', [$workspace, $project, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/tasks/Show')
            ->where('theme.brand', '#445566')
            ->where('task.title', 'Validate the collaboration surface')
            ->where('canComment', true)
            ->where('canManageTasks', true)
            ->has('comments', 1)
            ->where('comments.0.author_name', $member->name)
            ->has('activities', 3)
        );
});
