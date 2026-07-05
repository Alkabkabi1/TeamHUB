<?php

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('workspace has expected relationships', function () {
    $workspace = Workspace::factory()->create();

    expect($workspace->memberships())->toBeInstanceOf(HasMany::class)
        ->and($workspace->membershipRequests())->toBeInstanceOf(HasMany::class)
        ->and($workspace->files())->toBeInstanceOf(HasMany::class)
        ->and($workspace->updates())->toBeInstanceOf(HasMany::class)
        ->and($workspace->projects())->toBeInstanceOf(HasMany::class)
        ->and($workspace->members())->toBeInstanceOf(BelongsToMany::class);
});

test('workspace can load related membership requests and files', function () {
    $workspace = Workspace::factory()->create();

    WorkspaceMembershipRequest::factory()->count(2)->create(['workspace_id' => $workspace->id]);
    ProjectFile::factory()->count(3)->create(['workspace_id' => $workspace->id]);
    Project::factory()->count(2)->create(['workspace_id' => $workspace->id]);

    $workspace->load(['membershipRequests', 'files', 'projects']);

    expect($workspace->membershipRequests)->toHaveCount(2)
        ->and($workspace->files)->toHaveCount(3)
        ->and($workspace->projects)->toHaveCount(2);
});

test('force deleting workspace cascades to membership requests and files', function () {
    $workspace = Workspace::factory()->create();

    WorkspaceMembershipRequest::factory()->create(['workspace_id' => $workspace->id]);
    ProjectFile::factory()->create(['workspace_id' => $workspace->id]);
    Project::factory()->create(['workspace_id' => $workspace->id]);

    $workspaceId = $workspace->id;
    $workspace->forceDelete();

    expect(WorkspaceMembershipRequest::query()->where('workspace_id', $workspaceId)->exists())->toBeFalse()
        ->and(ProjectFile::query()->where('workspace_id', $workspaceId)->exists())->toBeFalse()
        ->and(Project::query()->where('workspace_id', $workspaceId)->exists())->toBeFalse();
});

test('workspace members relationship uses workspace memberships pivot', function () {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    expect($workspace->members)->toHaveCount(1)
        ->and($workspace->members->first()->id)->toBe($user->id);
});
