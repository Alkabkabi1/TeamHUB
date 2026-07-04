<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;

function projectOverviewLeadAndCommittee(): array
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

test('project overview exposes task stats and recent updates props', function () {
    [$lead, $club, $committee] = projectOverviewLeadAndCommittee();

    $todoTask = Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'status' => 'todo',
    ]);
    Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'status' => 'review',
        'due_at' => now()->subDay(),
    ]);

    Post::factory()->create([
        'club_id' => $club->id,
        'committee_id' => $committee->id,
        'user_id' => $lead->id,
        'title' => 'First sprint update',
        'published_at' => now(),
    ]);

    $todoTask->recordCreated($lead);

    $this->actingAs($lead)
        ->get(route('committees.manage', [$club, $committee]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Manage')
            ->where('taskStats.todo', 1)
            ->where('taskStats.review', 1)
            ->where('taskStats.overdue', 1)
            ->has('recentUpdates', 1)
            ->where('recentUpdates.0.title', 'First sprint update')
            ->has('recentActivities', 1)
            ->where('recentActivities.0.task_title', $todoTask->title)
        );
});
