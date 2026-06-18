<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;

test('club membership stores request review details', function () {
    $student = User::factory()->create();
    $reviewer = User::factory()->create();
    $club = Club::factory()->create();

    $membership = ClubMembership::query()->create([
        'user_id' => $student->id,
        'club_id' => $club->id,
        'role' => 'member',
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
