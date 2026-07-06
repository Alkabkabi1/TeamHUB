<?php

use App\Enums\WorkspaceCapability;
use App\Enums\WorkspaceRole;

test('club lead holds every capability', function () {
    expect(WorkspaceRole::WorkspaceLead->capabilities())->toBe(WorkspaceCapability::all());
});

test('membership manager owns member management and reporting', function () {
    $capabilities = WorkspaceRole::MembershipManager->capabilities();

    expect($capabilities)
        ->toContain(WorkspaceCapability::ManageMembers)
        ->toContain(WorkspaceCapability::ViewReports)
        ->not->toContain(WorkspaceCapability::ManageWorkspace);
});

test('member carries no management capability', function () {
    expect(WorkspaceRole::Member->capabilities())->toBe([])
        ->and(WorkspaceRole::Member->isManager())->toBeFalse();
});

test('manager role values exclude the plain member role', function () {
    expect(WorkspaceRole::managerRoleValues())
        ->not->toContain(WorkspaceRole::Member->value)
        ->toContain(WorkspaceRole::WorkspaceLead->value)
        ->toContain(WorkspaceRole::MembershipManager->value);
});

test('legacy roles map to named club roles', function () {
    expect(WorkspaceRole::fromLegacy('supervisor'))->toBe(WorkspaceRole::WorkspaceLead)
        ->and(WorkspaceRole::fromLegacy('club_supervisor'))->toBe(WorkspaceRole::WorkspaceLead)
        ->and(WorkspaceRole::fromLegacy('organizer'))->toBe(WorkspaceRole::WorkspaceLead)
        ->and(WorkspaceRole::fromLegacy('member'))->toBe(WorkspaceRole::Member)
        ->and(WorkspaceRole::fromLegacy(null))->toBe(WorkspaceRole::Member);
});
