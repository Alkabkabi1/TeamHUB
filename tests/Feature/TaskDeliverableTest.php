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
use App\Notifications\TaskChangesRequestedNotification;
use App\Notifications\TaskDeliverableApprovedNotification;
use App\Notifications\TaskSubmittedForReviewNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function deliveryProjectLeadAndCommittee(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $membership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);
    grantProjectLead($lead, $project);

    return [$lead, $workspace, $project];
}

function deliveryProjectMember(Workspace $workspace, Project $project, array $roles = [ProjectRole::Member]): User
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

test('an assignee can submit a deliverable with a file, link, and notes', function () {
    Storage::fake('public');
    Notification::fake();

    [$lead, $workspace, $project] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'in_progress',
    ]);

    $this->actingAs($member)
        ->post(route('projects.tasks.deliverable', [$workspace, $project, $task]), [
            'deliverable_file' => UploadedFile::fake()->create('demo.pdf', 200, 'application/pdf'),
            'deliverable_url' => 'https://figma.com/file/demo',
            'deliverable_notes' => 'Uploaded the draft and linked the design review.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('review')
        ->and($task->deliverable_url)->toBe('https://figma.com/file/demo')
        ->and($task->deliverable_notes)->toContain('draft')
        ->and($task->submitted_for_review_at)->not->toBeNull()
        ->and($task->getFirstMedia(Task::DELIVERABLE_COLLECTION))->not->toBeNull();

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.deliverable_submitted')->exists())
        ->toBeTrue();

    Notification::assertSentTo($lead, TaskSubmittedForReviewNotification::class);
});

test('a project lead can approve a submitted deliverable', function () {
    Notification::fake();

    [$lead, $workspace, $project] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'review',
        'submitted_for_review_at' => now()->subHour(),
    ]);

    $this->actingAs($lead)
        ->post(route('projects.tasks.approve', [$workspace, $project, $task]), [
            'review_notes' => 'Looks good. Shipping it.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('done')
        ->and($task->reviewed_by)->toBe($lead->id)
        ->and($task->completed_at)->not->toBeNull()
        ->and($task->review_notes)->toContain('Looks good');

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.deliverable_approved')->exists())
        ->toBeTrue();

    Notification::assertSentTo($member, TaskDeliverableApprovedNotification::class);
});

test('a project lead can request changes on a submitted deliverable', function () {
    Notification::fake();

    [$lead, $workspace, $project] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'review',
        'submitted_for_review_at' => now()->subHour(),
    ]);

    $this->actingAs($lead)
        ->post(route('projects.tasks.request-changes', [$workspace, $project, $task]), [
            'review_notes' => 'Please tighten the copy and re-upload the PDF.',
        ])
        ->assertRedirect(route('projects.tasks.show', [$workspace, $project, $task]));

    $task->refresh();

    expect($task->status->value)->toBe('in_progress')
        ->and($task->reviewed_by)->toBe($lead->id)
        ->and($task->completed_at)->toBeNull()
        ->and($task->review_notes)->toContain('tighten');

    expect(TaskActivity::query()->where('task_id', $task->id)->where('type', 'task.changes_requested')->exists())
        ->toBeTrue();

    Notification::assertSentTo($member, TaskChangesRequestedNotification::class);
});

test('non-managers cannot approve deliverables', function () {
    [$lead, $workspace, $project] = deliveryProjectLeadAndCommittee();
    $member = deliveryProjectMember($workspace, $project);
    $otherMember = deliveryProjectMember($workspace, $project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'review',
    ]);

    $this->actingAs($otherMember)
        ->post(route('projects.tasks.approve', [$workspace, $project, $task]))
        ->assertForbidden();
});
