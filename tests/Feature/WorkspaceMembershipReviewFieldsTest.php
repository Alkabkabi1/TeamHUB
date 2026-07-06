<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

test('workspace membership stores request review details', function () {
    $student = User::factory()->create();
    $reviewer = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $membership = WorkspaceMembership::query()->create([
        'user_id' => $student->id,
        'workspace_id' => $workspace->id,
        'status' => 'rejected',
        'requested_at' => now()->subDay(),
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now(),
        'rejection_reason' => 'Membership capacity is full.',
        'joined_at' => null,
    ]);

    expect($membership->fresh())
        ->status->toBe('rejected')
        ->rejection_reason->toBe('Membership capacity is full.')
        ->requested_at->not->toBeNull()
        ->reviewed_at->not->toBeNull()
        ->joined_at->toBeNull()
        ->and($membership->reviewer->is($reviewer))->toBeTrue();
});
