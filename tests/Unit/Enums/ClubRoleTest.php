<?php

use App\Enums\ClubCapability;
use App\Enums\ClubRole;

test('club lead holds every capability', function () {
    expect(ClubRole::ClubLead->capabilities())->toBe(ClubCapability::all());
});

test('membership manager owns member management and reporting', function () {
    $capabilities = ClubRole::MembershipManager->capabilities();

    expect($capabilities)
        ->toContain(ClubCapability::ManageMembers)
        ->toContain(ClubCapability::ViewReports)
        ->not->toContain(ClubCapability::ManageClub);
});

test('member carries no management capability', function () {
    expect(ClubRole::Member->capabilities())->toBe([])
        ->and(ClubRole::Member->isManager())->toBeFalse();
});

test('manager role values exclude the plain member role', function () {
    expect(ClubRole::managerRoleValues())
        ->not->toContain(ClubRole::Member->value)
        ->toContain(ClubRole::ClubLead->value)
        ->toContain(ClubRole::MembershipManager->value);
});

test('legacy roles map to named club roles', function () {
    expect(ClubRole::fromLegacy('supervisor'))->toBe(ClubRole::ClubLead)
        ->and(ClubRole::fromLegacy('club_supervisor'))->toBe(ClubRole::ClubLead)
        ->and(ClubRole::fromLegacy('organizer'))->toBe(ClubRole::ClubLead)
        ->and(ClubRole::fromLegacy('member'))->toBe(ClubRole::Member)
        ->and(ClubRole::fromLegacy(null))->toBe(ClubRole::Member);
});
