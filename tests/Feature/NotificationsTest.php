<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\Mail;

function notificationLeadAndCommittee(): array
{
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $project = Project::factory()->create(['workspace_id' => $workspace->id]);
    $lead = User::factory()->student()->create();

    $workspaceMembership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'workspace_id' => $workspace->id,
    ]);
    $workspaceMembership->syncWorkspaceRoles([WorkspaceRole::WorkspaceLead]);
    grantProjectLead($lead, $project);

    return [$lead, $workspace, $project];
}

function notificationMember(Workspace $workspace, Project $project): User
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

test('members can view unread task notifications and mark them as read', function () {
    Mail::fake();

    [$lead, $workspace, $project] = notificationLeadAndCommittee();
    $member = notificationMember($workspace, $project);

    $this->actingAs($lead)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Ship the notification center',
            'assigned_to' => $member->id,
            'priority' => 'high',
            'status' => 'todo',
        ])
        ->assertRedirect();

    $notification = $member->notifications()->latest()->firstOrFail();

    $this->actingAs($member)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->where('auth.user.unread_notifications_count', 1)
            ->has('unreadNotifications', 1)
        );

    $this->actingAs($member)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($member->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($member->notifications()->firstWhere('id', $notification->id)?->read_at)->not->toBeNull();
});

test('members can mark all notifications as read', function () {
    Mail::fake();

    [$lead, $workspace, $project] = notificationLeadAndCommittee();
    $member = notificationMember($workspace, $project);

    $this->actingAs($lead)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'First unread task',
            'assigned_to' => $member->id,
            'priority' => 'medium',
            'status' => 'todo',
        ])
        ->assertRedirect();

    $this->actingAs($lead)
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Second unread task',
            'assigned_to' => $member->id,
            'priority' => 'medium',
            'status' => 'todo',
        ])
        ->assertRedirect();

    expect($member->fresh()->unreadNotifications()->count())->toBe(2);

    $this->actingAs($member)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($member->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($member->notifications()->whereNull('read_at')->count())->toBe(0);
});
