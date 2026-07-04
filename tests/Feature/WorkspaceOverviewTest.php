<?php

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;

function workspaceLeadAndClub(): array
{
    $club = Club::factory()->create(['status' => 'active']);
    $lead = User::factory()->student()->create();

    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles([ClubRole::ClubLead]);

    return [$lead, $club];
}

test('workspace overview exposes project cards and recent activity', function () {
    [$lead, $club] = workspaceLeadAndClub();
    $committee = Committee::factory()->create(['club_id' => $club->id, 'name' => 'Website Refresh']);

    Task::factory()->create([
        'committee_id' => $committee->id,
        'created_by' => $lead->id,
        'title' => 'Ship landing page',
        'due_at' => now()->subDay(),
        'status' => 'in_progress',
    ]);

    Post::factory()->create([
        'club_id' => $club->id,
        'user_id' => $lead->id,
        'title' => 'Workspace kickoff update',
        'published_at' => now(),
    ]);

    $this->actingAs($lead)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('workspaceStats.projects_count', 1)
            ->where('workspaceStats.tasks_count', 1)
            ->where('workspaceStats.overdue_tasks_count', 1)
            ->has('workspaceProjects', 1)
            ->where('workspaceProjects.0.name', 'Website Refresh')
            ->has('recentActivity')
        );
});

test('workspace members page loads for club leads', function () {
    [$lead, $club] = workspaceLeadAndClub();

    $this->actingAs($lead)
        ->get(route('clubs.manage.members', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Members')
            ->where('club.id', $club->id)
            ->has('members')
            ->has('pendingApplications')
        );
});
