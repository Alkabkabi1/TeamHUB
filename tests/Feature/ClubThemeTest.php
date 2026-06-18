<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ----------------------------------------------------------------
// Edit page (GET)
// ----------------------------------------------------------------

test('supervisor can view club theme edit page', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.theme.edit', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ClubTheme')
            ->has('club')
            ->where('club.id', $club->id)
            ->where('club.name', $club->name)
        );
});

test('university staff can view club theme edit page', function () {
    $staff = User::factory()->universityStaff()->create();
    $club = Club::factory()->create();

    $this->actingAs($staff)
        ->get(route('clubs.theme.edit', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('ClubTheme'));
});

test('non-supervisor student cannot access club theme edit page', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();

    $this->actingAs($student)
        ->get(route('clubs.theme.edit', $club))
        ->assertForbidden();
});

test('theme edit page returns logoUrl as null when club has no logo', function () {
    $staff = User::factory()->universityStaff()->create();
    $club = Club::factory()->create();

    $this->actingAs($staff)
        ->get(route('clubs.theme.edit', $club))
        ->assertInertia(fn ($page) => $page
            ->where('logoUrl', null)
        );
});

// ----------------------------------------------------------------
// Update (PUT)
// ----------------------------------------------------------------

test('supervisor can update club theme color', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['theme' => null]);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->put(route('clubs.theme.update', $club), [
            'theme' => '#1A2B3C',
        ])
        ->assertRedirect(route('clubs.theme.edit', $club));

    expect($club->fresh()->theme)->toBe('#1A2B3C');
});

test('university staff can update club theme color', function () {
    $staff = User::factory()->universityStaff()->create();
    $club = Club::factory()->create(['theme' => null]);

    $this->actingAs($staff)
        ->put(route('clubs.theme.update', $club), [
            'theme' => '#ABCDEF',
        ])
        ->assertRedirect(route('clubs.theme.edit', $club));

    expect($club->fresh()->theme)->toBe('#ABCDEF');
});

test('logo upload is stored in the media library', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $this->actingAs($supervisor)
        ->put(route('clubs.theme.update', $club), [
            'theme' => '#006471',
            'logo' => $logo,
        ])
        ->assertRedirect(route('clubs.theme.edit', $club));

    $media = $club->fresh()->getFirstMedia(Club::LOGO_COLLECTION);

    expect($media)->not()->toBeNull();
    expect($club->fresh()->logo_url)->not()->toBeNull();
    Storage::disk('public')->assertExists("{$media->id}/{$media->file_name}");
});

test('old logo is replaced when a new logo is uploaded', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    // Upload first logo
    $this->actingAs($supervisor)
        ->put(route('clubs.theme.update', $club), [
            'theme' => '#006471',
            'logo' => UploadedFile::fake()->image('first.png', 100, 100),
        ]);

    $firstMedia = $club->fresh()->getFirstMedia(Club::LOGO_COLLECTION);
    Storage::disk('public')->assertExists("{$firstMedia->id}/{$firstMedia->file_name}");

    // Upload replacement logo
    $this->actingAs($supervisor)
        ->put(route('clubs.theme.update', $club), [
            'theme' => '#1A2B3C',
            'logo' => UploadedFile::fake()->image('second.png', 100, 100),
        ]);

    // Single-file collection keeps exactly one logo; the old file is gone.
    expect($club->fresh()->getMedia(Club::LOGO_COLLECTION))->toHaveCount(1);
    Storage::disk('public')->assertMissing("{$firstMedia->id}/{$firstMedia->file_name}");
});

test('invalid hex color is rejected with validation error', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->put(route('clubs.theme.update', $club), [
            'theme' => 'not-a-color',
        ])
        ->assertSessionHasErrors('theme');
});

test('logo larger than 5 MB is rejected with validation error', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    // 6 MB file (6144 KB)
    $oversizedLogo = UploadedFile::fake()->image('big.png')->size(6144);

    $this->actingAs($supervisor)
        ->put(route('clubs.theme.update', $club), [
            'theme' => '#006471',
            'logo' => $oversizedLogo,
        ])
        ->assertSessionHasErrors('logo');
});

test('non-supervisor cannot update club theme', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();

    $this->actingAs($student)
        ->put(route('clubs.theme.update', $club), [
            'theme' => '#006471',
        ])
        ->assertForbidden();
});
