<?php

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Post;
use App\Models\User;

function projectUpdatesLeadAndCommittee(): array
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

test('project updates page lists committee news posts', function () {
    [$lead, $club, $committee] = projectUpdatesLeadAndCommittee();

    Post::factory()->create([
        'club_id' => $club->id,
        'committee_id' => $committee->id,
        'user_id' => $lead->id,
        'title' => 'Milestone recap',
    ]);

    $this->actingAs($lead)
        ->get(route('committees.updates.index', [$club, $committee]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Updates')
            ->has('updates', 1)
            ->where('updates.0.title', 'Milestone recap')
        );
});

test('creating a committee update redirects back to the project updates page', function () {
    [$lead, $club, $committee] = projectUpdatesLeadAndCommittee();

    $this->actingAs($lead)
        ->post(route('committees.news.store', [$club, $committee]), [
            'title' => 'Launch note',
            'body' => 'We shipped the first project shell.',
        ])
        ->assertRedirect(route('committees.updates.index', [$club, $committee]));
});
