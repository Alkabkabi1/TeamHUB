<?php

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

function projectUpdatesLeadAndCommittee(): array
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

test('project updates page lists committee news posts', function () {
    [$lead, $workspace, $project] = projectUpdatesLeadAndCommittee();

    ProjectUpdate::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'user_id' => $lead->id,
        'title' => 'Milestone recap',
    ]);

    $this->actingAs($lead)
        ->get(route('projects.updates.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/Updates')
            ->has('updates', 1)
            ->where('updates.0.title', 'Milestone recap')
        );
});

test('creating a committee update redirects back to the project updates page', function () {
    [$lead, $workspace, $project] = projectUpdatesLeadAndCommittee();

    $this->actingAs($lead)
        ->post(route('projects.updates.store', [$workspace, $project]), [
            'title' => 'Launch note',
            'body' => 'We shipped the first project shell.',
        ])
        ->assertRedirect(route('projects.updates.index', [$workspace, $project]));
});
