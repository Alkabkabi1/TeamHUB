<?php

use App\Models\ProjectFile;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use Database\Seeders\DatabaseSeeder;

test('database seeder populates demo users and workspaces', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'student@teamhub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'workspace-lead@teamhub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'project-lead@teamhub.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'admin@teamhub.test')->exists())->toBeTrue()
        ->and(Workspace::query()->where('name', 'مساحة الحاسبات')->exists())->toBeTrue()
        ->and(Workspace::count())->toBeGreaterThanOrEqual(6);
});

test('database seeder populates memberships and resources', function () {
    $this->seed(DatabaseSeeder::class);

    expect(WorkspaceMembership::count())->toBeGreaterThan(0)
        ->and(ProjectFile::count())->toBeGreaterThan(0)
        ->and(ProjectUpdate::count())->toBeGreaterThan(0)
        ->and(WorkspaceMembershipRequest::query()->where('status', 'pending')->exists())->toBeTrue();
});

test('student demo user has workspace memberships after seeding', function () {
    $this->seed(DatabaseSeeder::class);

    $student = User::query()->where('email', 'student@teamhub.test')->first();

    expect($student)->not->toBeNull()
        ->and($student->workspaceMemberships()->count())->toBeGreaterThanOrEqual(2);
});

test('seeded workspace memberships are approved for active members', function () {
    $this->seed(DatabaseSeeder::class);

    expect(WorkspaceMembership::query()->where('status', 'approved')->whereNotNull('joined_at')->count())
        ->toBeGreaterThan(0);
});

test('seeded data includes pending workspace memberships for cs workspace', function () {
    $this->seed(DatabaseSeeder::class);

    $csClub = Workspace::query()->where('name', 'مساحة الحاسبات')->first();

    expect($csClub)->not->toBeNull()
        ->and(WorkspaceMembership::query()
            ->where('workspace_id', $csClub->id)
            ->where('status', 'pending')
            ->count())->toBeGreaterThanOrEqual(1);
});

test('seeded data includes rejected join applications', function () {
    $this->seed(DatabaseSeeder::class);

    expect(WorkspaceMembershipRequest::query()->where('status', 'rejected')->exists())->toBeTrue();
});

test('seeded data includes approved join applications', function () {
    $this->seed(DatabaseSeeder::class);

    expect(WorkspaceMembershipRequest::query()->where('status', 'approved')->exists())->toBeTrue();
});

test('database seeder can run twice without errors', function () {
    $this->seed(DatabaseSeeder::class);

    $workspacesAfterFirst = Workspace::count();

    expect(fn () => $this->seed(DatabaseSeeder::class))->not->toThrow(Throwable::class)
        ->and(Workspace::count())->toBe($workspacesAfterFirst);
});
