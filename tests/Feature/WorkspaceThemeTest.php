<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ----------------------------------------------------------------
// Edit page (GET)
// ----------------------------------------------------------------

test('supervisor can view club theme edit page', function () {
    $supervisor = User::factory()->workspaceSupervisor()->create();
    $workspace = Workspace::factory()->create();

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('workspaces.theme.edit', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('WorkspaceTheme')
            ->has('workspace')
            ->where('workspace.id', $workspace->id)
            ->where('workspace.name', $workspace->name)
        );
});

test('university staff can view club theme edit page', function () {
    $staff = User::factory()->universityStaff()->create();
    $workspace = Workspace::factory()->create();

    $this->actingAs($staff)
        ->get(route('workspaces.theme.edit', $workspace))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('WorkspaceTheme'));
});

test('non-supervisor student cannot access club theme edit page', function () {
    $student = User::factory()->student()->create();
    $workspace = Workspace::factory()->create();

    $this->actingAs($student)
        ->get(route('workspaces.theme.edit', $workspace))
        ->assertForbidden();
});

test('theme edit page returns logoUrl as null when club has no logo', function () {
    $staff = User::factory()->universityStaff()->create();
    $workspace = Workspace::factory()->create();

    $this->actingAs($staff)
        ->get(route('workspaces.theme.edit', $workspace))
        ->assertInertia(fn ($page) => $page
            ->where('logoUrl', null)
        );
});

// ----------------------------------------------------------------
// Update (PUT)
// ----------------------------------------------------------------

test('supervisor can update club theme color', function () {
    $supervisor = User::factory()->workspaceSupervisor()->create();
    $workspace = Workspace::factory()->create(['theme' => null]);

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($supervisor)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => '#1A2B3C',
        ])
        ->assertRedirect(route('workspaces.theme.edit', $workspace));

    expect($workspace->fresh()->theme)->toBe('#1A2B3C');
});

test('university staff can update club theme color', function () {
    $staff = User::factory()->universityStaff()->create();
    $workspace = Workspace::factory()->create(['theme' => null]);

    $this->actingAs($staff)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => '#ABCDEF',
        ])
        ->assertRedirect(route('workspaces.theme.edit', $workspace));

    expect($workspace->fresh()->theme)->toBe('#ABCDEF');
});

test('logo upload is stored in the media library', function () {
    Storage::fake('public');

    $supervisor = User::factory()->workspaceSupervisor()->create();
    $workspace = Workspace::factory()->create();

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $this->actingAs($supervisor)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => '#c8924a',
            'logo' => $logo,
        ])
        ->assertRedirect(route('workspaces.theme.edit', $workspace));

    $media = $workspace->fresh()->getFirstMedia(Workspace::LOGO_COLLECTION);

    expect($media)->not()->toBeNull();
    expect($workspace->fresh()->logo_url)->not()->toBeNull();
    Storage::disk('public')->assertExists("{$media->id}/{$media->file_name}");
});

test('old logo is replaced when a new logo is uploaded', function () {
    Storage::fake('public');

    $supervisor = User::factory()->workspaceSupervisor()->create();
    $workspace = Workspace::factory()->create();

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    // Upload first logo
    $this->actingAs($supervisor)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => '#c8924a',
            'logo' => UploadedFile::fake()->image('first.png', 100, 100),
        ]);

    $firstMedia = $workspace->fresh()->getFirstMedia(Workspace::LOGO_COLLECTION);
    Storage::disk('public')->assertExists("{$firstMedia->id}/{$firstMedia->file_name}");

    // Upload replacement logo
    $this->actingAs($supervisor)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => '#1A2B3C',
            'logo' => UploadedFile::fake()->image('second.png', 100, 100),
        ]);

    // Single-file collection keeps exactly one logo; the old file is gone.
    expect($workspace->fresh()->getMedia(Workspace::LOGO_COLLECTION))->toHaveCount(1);
    Storage::disk('public')->assertMissing("{$firstMedia->id}/{$firstMedia->file_name}");
});

test('invalid hex color is rejected with validation error', function () {
    $supervisor = User::factory()->workspaceSupervisor()->create();
    $workspace = Workspace::factory()->create();

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    $this->actingAs($supervisor)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => 'not-a-color',
        ])
        ->assertSessionHasErrors('theme');
});

test('logo larger than 5 MB is rejected with validation error', function () {
    Storage::fake('public');

    $supervisor = User::factory()->workspaceSupervisor()->create();
    $workspace = Workspace::factory()->create();

    WorkspaceMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'workspace_id' => $workspace->id,
    ]);

    // 6 MB file (6144 KB)
    $oversizedLogo = UploadedFile::fake()->image('big.png')->size(6144);

    $this->actingAs($supervisor)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => '#c8924a',
            'logo' => $oversizedLogo,
        ])
        ->assertSessionHasErrors('logo');
});

test('non-supervisor cannot update club theme', function () {
    $student = User::factory()->student()->create();
    $workspace = Workspace::factory()->create();

    $this->actingAs($student)
        ->put(route('workspaces.theme.update', $workspace), [
            'theme' => '#c8924a',
        ])
        ->assertForbidden();
});
