<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\User;

function taskDetailLeadAndProject(): array
{
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $lead = User::factory()->student()->create();

    $clubMembership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $clubMembership->syncClubRoles([ClubRole::ClubLead]);

    $committeeMembership = CommitteeMembership::factory()->create([
        'user_id' => $lead->id,
        'committee_id' => $committee->id,
    ]);
    $committeeMembership->syncCommitteeRoles([CommitteeRole::CommitteeLead, CommitteeRole::Member]);

    return [$lead, $club, $committee];
}

function taskDetailMember(Club $club, Committee $committee): User
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
    $membership->syncCommitteeRoles([CommitteeRole::Member]);

    return $user;
}

test('task detail page renders comments, activities, and collaboration flags', function () {
    [$lead, $club, $committee] = taskDetailLeadAndProject();
    $member = taskDetailMember($club, $committee);

    $task = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'assigned_to' => $member->id,
        'status' => 'in_progress',
        'title' => 'Validate the collaboration surface',
    ]);

    $task->recordCreated($lead);
    $task->recordAssignment($lead, null, $member);
    $task->addComment($member, 'The main draft is ready.');

    $this->actingAs($lead)
        ->get(route('committees.tasks.show', [$club, $committee, $task]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/tasks/Show')
            ->where('task.title', 'Validate the collaboration surface')
            ->where('canComment', true)
            ->where('canManageTasks', true)
            ->has('comments', 1)
            ->where('comments.0.author_name', $member->name)
            ->has('activities', 3)
        );
});
