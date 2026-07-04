<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\User;

function policyProjectLeadAndCommittee(): array
{
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $lead = User::factory()->student()->create();

    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles([ClubRole::ClubLead]);

    return [$lead, $club, $committee];
}

function policyProjectMember(Club $club, Committee $committee, array $roles = [CommitteeRole::Member]): User
{
    $user = User::factory()->student()->create();

    ClubMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'club_id' => $club->id,
    ]);

    $membership = CommitteeMembership::factory()->create([
        'user_id' => $user->id,
        'committee_id' => $committee->id,
    ]);
    $membership->syncCommitteeRoles($roles);

    return $user;
}

test('project leads can create, update, delete, and review tasks', function () {
    [$lead, $club, $committee] = policyProjectLeadAndCommittee();
    $member = policyProjectMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
    ]);

    expect($lead->can('viewAny', [Task::class, $committee]))->toBeTrue()
        ->and($lead->can('create', [Task::class, $committee]))->toBeTrue()
        ->and($lead->can('update', $task))->toBeTrue()
        ->and($lead->can('delete', $task))->toBeTrue()
        ->and($lead->can('approveDeliverable', $task))->toBeTrue();
});

test('approved project members can view tasks and update only their own assigned task', function () {
    [$lead, $club, $committee] = policyProjectLeadAndCommittee();
    $member = policyProjectMember($club, $committee);
    $otherMember = policyProjectMember($club, $committee);

    $assignedTask = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
    ]);
    $otherTask = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $otherMember->id,
    ]);

    expect($member->can('viewAny', [Task::class, $committee]))->toBeTrue()
        ->and($member->can('view', $assignedTask))->toBeTrue()
        ->and($member->can('update', $assignedTask))->toBeTrue()
        ->and($member->can('submitDeliverable', $assignedTask))->toBeTrue()
        ->and($member->can('update', $otherTask))->toBeFalse()
        ->and($member->can('approveDeliverable', $assignedTask))->toBeFalse();
});

test('outsiders cannot view or manage project tasks', function () {
    [$lead, , $committee] = policyProjectLeadAndCommittee();
    $outsider = User::factory()->student()->create();

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
    ]);

    expect($outsider->can('viewAny', [Task::class, $committee]))->toBeFalse()
        ->and($outsider->can('view', $task))->toBeFalse()
        ->and($outsider->can('update', $task))->toBeFalse()
        ->and($outsider->can('delete', $task))->toBeFalse();
});
