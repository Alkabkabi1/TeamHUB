<?php

use App\Enums\WorkspaceRole;
use App\Models\WorkspaceMembership;

test('club membership factory creates approved member by default', function () {
    $membership = WorkspaceMembership::factory()->create();

    expect($membership->status)->toBe('approved')
        ->and($membership->hasWorkspaceRole(WorkspaceRole::Member))->toBeTrue()
        ->and($membership->joined_at)->not->toBeNull()
        ->and($membership->user_id)->not->toBeNull()
        ->and($membership->workspace_id)->not->toBeNull();
});

test('pending club membership factory state clears joined_at', function () {
    $membership = WorkspaceMembership::factory()->pending()->create();

    expect($membership->status)->toBe('pending')
        ->and($membership->joined_at)->toBeNull()
        ->and($membership->reviewed_at)->toBeNull();
});

test('supervisor club membership factory state grants the club lead role', function () {
    $membership = WorkspaceMembership::factory()->supervisor()->create();

    expect($membership->hasWorkspaceRole(WorkspaceRole::WorkspaceLead))->toBeTrue();
});

test('rejected club membership factory state stores reason', function () {
    $membership = WorkspaceMembership::factory()->rejected('غير مؤهل')->create();

    expect($membership->status)->toBe('rejected')
        ->and($membership->rejection_reason)->toBe('غير مؤهل')
        ->and($membership->joined_at)->toBeNull();
});
