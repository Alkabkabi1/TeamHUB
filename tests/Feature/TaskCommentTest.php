<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function taskCommentLeadAndCommittee(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    $workspaceMembership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $workspaceMembership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);

    return [$lead, $workspace, $project];
}

function approvedTaskCommentMember(Workspace $workspace, Project $project, array $roles = [ProjectRole::Member]): User
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
    $membership->syncProjectRoles($roles);

    return $user;
}

test('project members can add comments and comments appear in task activity', function () {
    [$lead, $workspace, $project] = taskCommentLeadAndCommittee();
    $member = approvedTaskCommentMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
    ]);

    $this->actingAs($member)
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'I uploaded the first draft and would like feedback.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    expect(TaskComment::query()->where('task_id', $task->id)->count())->toBe(1)
        ->and(TaskActivity::query()->where('task_id', $task->id)->where('type', 'comment.added')->exists())->toBeTrue();
});

test('authors and project leads can delete task comments, but other members cannot', function () {
    [$lead, $workspace, $project] = taskCommentLeadAndCommittee();
    $author = approvedTaskCommentMember($workspace, $project);
    $otherMember = approvedTaskCommentMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $author->id,
    ]);

    $comment = $task->addComment($author, 'Draft delivered.');

    $this->actingAs($otherMember)
        ->delete(route('projects.tasks.comments.destroy', [$workspace, $project, $task, $comment]))
        ->assertForbidden();

    $this->actingAs($lead)
        ->delete(route('projects.tasks.comments.destroy', [$workspace, $project, $task, $comment]))
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
});
