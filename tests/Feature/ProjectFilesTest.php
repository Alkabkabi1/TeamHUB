<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function projectFilesLeadAndCommittee(): array
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

function projectFilesMember(Workspace $workspace, Project $project): User
{
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

    return $member;
}

test('approved project members can view the project files page', function () {
    [$lead, $workspace, $project] = projectFilesLeadAndCommittee();
    $member = projectFilesMember($workspace, $project);

    ProjectFile::factory()->forProject($project)->create([
        'title' => 'Design brief',
    ]);

    $this->actingAs($member)
        ->get(route('projects.files.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Files')
            ->has('files', 1)
            ->where('files.0.title', 'Design brief')
        );
});

test('project leads can upload and delete project files', function () {
    Storage::fake('public');

    [$lead, $workspace, $project] = projectFilesLeadAndCommittee();

    $this->actingAs($lead)
        ->post(route('projects.files.store', [$workspace, $project]), [
            'title' => 'Sprint assets',
            'description' => 'Shared image pack',
            'type' => ProjectFile::TYPE_MEDIA,
            'access' => 'عام',
            'file' => UploadedFile::fake()->image('assets.png'),
        ])
        ->assertRedirect();

    $resource = ProjectFile::query()->where('project_id', $project->id)->firstOrFail();

    expect($resource->title)->toBe('Sprint assets');
    Storage::disk('public')->assertExists($resource->file_path);

    $this->actingAs($lead)
        ->delete(route('projects.files.destroy', [$workspace, $project, $resource]))
        ->assertRedirect();

    $this->assertDatabaseMissing('project_files', ['id' => $resource->id]);
});
