<?php

use App\Enums\WorkspaceStatus;
use App\Models\Workspace;

test('workspace factory defaults to active status with arabic name prefix', function () {
    $workspace = Workspace::factory()->make();

    expect($workspace->status)->toBe(WorkspaceStatus::Active)
        ->and($workspace->name)->toStartWith('مساحة عمل ');
});

test('inactive factory state sets inactive status', function () {
    $workspace = Workspace::factory()->inactive()->make();

    expect($workspace->status)->toBe(WorkspaceStatus::Inactive);
});

test('founding factory state sets founding status', function () {
    $workspace = Workspace::factory()->founding()->make();

    expect($workspace->status)->toBe(WorkspaceStatus::Founding);
});

test('with theme factory state sets theme color', function () {
    $workspace = Workspace::factory()->withTheme('#c8924a')->make();

    expect($workspace->theme)->toBe('#c8924a');
});
