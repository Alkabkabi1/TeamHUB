<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('workspace membership request belongs to workspace user and reviewer', function () {
    $application = WorkspaceMembershipRequest::factory()->create();

    expect($application->workspace())->toBeInstanceOf(BelongsTo::class)
        ->and($application->workspace->id)->toBe($application->workspace_id)
        ->and($application->user())->toBeInstanceOf(BelongsTo::class)
        ->and($application->user->id)->toBe($application->user_id);
});

test('workspace membership request casts attributes correctly', function () {
    $reviewer = User::factory()->create();
    $reviewedAt = now()->startOfSecond();

    $application = WorkspaceMembershipRequest::factory()->approved()->create([
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => $reviewedAt,
        'weekly_hours' => 6,
    ]);

    expect($application->weekly_hours)->toBeInt()->toBe(6)
        ->and($application->reviewed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($application->reviewed_at->equalTo($reviewedAt))->toBeTrue()
        ->and($application->reviewer->id)->toBe($reviewer->id);
});

test('pending factory state keeps application awaiting review', function () {
    $application = WorkspaceMembershipRequest::factory()->pending()->create();

    expect($application->status)->toBe('pending')
        ->and($application->reviewed_at)->toBeNull()
        ->and($application->reviewed_by)->toBeNull();
});

test('approved factory state records review metadata', function () {
    $application = WorkspaceMembershipRequest::factory()->approved()->create();

    expect($application->status)->toBe('approved')
        ->and($application->reviewed_at)->not->toBeNull();
});

test('rejected factory state records review metadata', function () {
    $reviewer = User::factory()->clubSupervisor()->create();
    $reviewedAt = now()->startOfSecond();

    $application = WorkspaceMembershipRequest::factory()->rejected()->create([
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => $reviewedAt,
    ]);

    expect($application->status)->toBe('rejected')
        ->and($application->reviewed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($application->reviewed_at->equalTo($reviewedAt))->toBeTrue()
        ->and($application->reviewer->id)->toBe($reviewer->id);
});

test('workspace membership request is mass assignable', function () {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    $application = WorkspaceMembershipRequest::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'full_name' => 'وئام راشد',
        'phone' => '0500000000',
        'skills' => 'برمجة',
        'weekly_hours' => 4,
        'tools' => 'VS Code',
        'motivation' => 'التطوع',
        'contribution' => 'الفعاليات',
        'status' => 'pending',
    ]);

    expect($application->fresh())
        ->full_name->toBe('وئام راشد')
        ->skills->toBe('برمجة')
        ->status->toBe('pending');
});
