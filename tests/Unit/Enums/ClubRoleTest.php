<?php

use App\Enums\ClubCapability;
use App\Enums\ClubRole;

test('club lead holds every capability', function () {
    expect(ClubRole::ClubLead->capabilities())->toBe(ClubCapability::all());
});

test('events manager owns the full event lifecycle', function () {
    $capabilities = ClubRole::EventsManager->capabilities();

    expect($capabilities)
        ->toContain(ClubCapability::ManageEvents)
        ->toContain(ClubCapability::ManageVolunteerHours)
        ->toContain(ClubCapability::IssueCertificates)
        ->toContain(ClubCapability::ViewReports)
        ->not->toContain(ClubCapability::ManageNews)
        ->not->toContain(ClubCapability::ManageMembers);
});

test('member carries no management capability', function () {
    expect(ClubRole::Member->capabilities())->toBe([])
        ->and(ClubRole::Member->isManager())->toBeFalse();
});

test('manager role values exclude the plain member role', function () {
    expect(ClubRole::managerRoleValues())
        ->not->toContain(ClubRole::Member->value)
        ->toContain(ClubRole::ClubLead->value)
        ->toContain(ClubRole::EventsManager->value);
});

test('legacy roles map to named club roles', function () {
    expect(ClubRole::fromLegacy('supervisor'))->toBe(ClubRole::ClubLead)
        ->and(ClubRole::fromLegacy('club_supervisor'))->toBe(ClubRole::ClubLead)
        ->and(ClubRole::fromLegacy('organizer'))->toBe(ClubRole::EventsManager)
        ->and(ClubRole::fromLegacy('member'))->toBe(ClubRole::Member)
        ->and(ClubRole::fromLegacy(null))->toBe(ClubRole::Member);
});
