<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A student who is an approved ClubLead of a freshly created club.
 *
 * @return array{0: User, 1: Club}
 */
function clubLeadAndClub(): array
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

test('the public committee listing and page render for anyone', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $this->get(route('committees.index', $club))->assertOk();
    $this->get(route('committees.show', [$club, $committee]))->assertOk();
});

test('a committee from another club 404s under a mismatched club', function () {
    $clubA = Club::factory()->create(['status' => 'active']);
    $clubB = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $clubA->id]);

    $this->get(route('committees.show', [$clubB, $committee]))->assertNotFound();
});

test('a club lead can create a committee and becomes its lead', function () {
    [$lead, $club] = clubLeadAndClub();

    $this->actingAs($lead)
        ->post(route('committees.store', $club), ['name' => 'اللجنة العلمية'])
        ->assertRedirect();

    $committee = Committee::query()->where('club_id', $club->id)->firstOrFail();

    expect($committee->name)->toBe('اللجنة العلمية')
        ->and($lead->fresh()->canManageCommittee($committee))->toBeTrue();
});

test('a plain student cannot create a committee', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('committees.store', $club), ['name' => 'لجنة'])
        ->assertForbidden();
});

test('the committee dashboard is forbidden to non-managers and visible to managers', function () {
    [$lead, $club] = clubLeadAndClub();
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $outsider = User::factory()->student()->create();

    $this->actingAs($outsider)
        ->get(route('committees.manage', [$club, $committee]))
        ->assertForbidden();

    $this->actingAs($lead)
        ->get(route('committees.manage', [$club, $committee]))
        ->assertOk();
});

test('an approved club member can request to join a committee', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $student = User::factory()->student()->create();
    ClubMembership::factory()->approved()->create([
        'user_id' => $student->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($student)
        ->post(route('committees.join', [$club, $committee]))
        ->assertRedirect();

    $this->assertDatabaseHas('committee_memberships', [
        'committee_id' => $committee->id,
        'user_id' => $student->id,
        'status' => 'pending',
    ]);
});

test('a non-club-member cannot request to join a committee', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('committees.join', [$club, $committee]))
        ->assertForbidden();
});

test('a committee manager can approve a pending join request', function () {
    [$lead, $club] = clubLeadAndClub();
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $applicant = User::factory()->student()->create();
    $pending = CommitteeMembership::factory()->pending()->create([
        'user_id' => $applicant->id,
        'committee_id' => $committee->id,
    ]);

    $this->actingAs($lead)
        ->post(route('committees.memberships.approve', [$club, $committee, $pending]))
        ->assertRedirect();

    expect($pending->fresh()->status)->toBe('approved');
});

test('a committee-scoped event is created with the committee id and enforces capability', function () {
    [$lead, $club] = clubLeadAndClub();
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $this->actingAs($lead)
        ->post(route('committees.events.store', [$club, $committee]), [
            'title' => 'ورشة عمل',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
            'status' => 'active',
        ])
        ->assertRedirect(route('committees.manage', [$club, $committee]));

    $this->assertDatabaseHas('events', [
        'club_id' => $club->id,
        'committee_id' => $committee->id,
        'title' => 'ورشة عمل',
    ]);
});

test('a non-manager cannot create a committee event', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('committees.events.store', [$club, $committee]), [
            'title' => 'x',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ])
        ->assertForbidden();
});

test('a committee-scoped news post is created with the committee id', function () {
    [$lead, $club] = clubLeadAndClub();
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $this->actingAs($lead)
        ->post(route('committees.news.store', [$club, $committee]), [
            'title' => 'خبر اللجنة',
            'body' => 'محتوى الخبر',
        ])
        ->assertRedirect(route('committees.updates.index', [$club, $committee]));

    $this->assertDatabaseHas('posts', [
        'club_id' => $club->id,
        'committee_id' => $committee->id,
        'title' => 'خبر اللجنة',
    ]);
});

test('the last committee lead cannot be removed', function () {
    [$lead, $club] = clubLeadAndClub();
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    // The lead becomes the committee's sole CommitteeLead.
    $membership = CommitteeMembership::factory()->create([
        'user_id' => $lead->id,
        'committee_id' => $committee->id,
        'status' => 'approved',
    ]);
    $membership->syncCommitteeRoles([CommitteeRole::CommitteeLead, CommitteeRole::Member]);

    $this->actingAs($lead)
        ->delete(route('committees.members.destroy', [$club, $committee, $membership]))
        ->assertRedirect();

    $this->assertDatabaseHas('committee_memberships', ['id' => $membership->id]);
});

test('archiving a committee soft-deletes it', function () {
    [$lead, $club] = clubLeadAndClub();
    $committee = Committee::factory()->create(['club_id' => $club->id]);

    $this->actingAs($lead)
        ->delete(route('committees.destroy', [$club, $committee]))
        ->assertRedirect(route('committees.index', $club));

    $this->assertSoftDeleted('committees', ['id' => $committee->id]);
});

test('a club lead can open committee settings with managed project switcher context', function () {
    [$lead, $club] = clubLeadAndClub();
    $committeeA = Committee::factory()->create(['club_id' => $club->id, 'name' => 'Project Alpha']);
    $committeeB = Committee::factory()->create(['club_id' => $club->id, 'name' => 'Project Beta']);

    $this->actingAs($lead)
        ->get(route('committees.edit', [$club, $committeeA]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Form')
            ->where('mode', 'edit')
            ->where('committee.id', $committeeA->id)
            ->has('auth.user.managed_committees', 2)
        );
});

test('a club lead can update committee settings', function () {
    [$lead, $club] = clubLeadAndClub();
    $committee = Committee::factory()->create([
        'club_id' => $club->id,
        'name' => 'Legacy Project Name',
        'description' => 'Old description',
    ]);

    $this->actingAs($lead)
        ->put(route('committees.update', [$club, $committee]), [
            'name' => 'Validation Project Name',
            'description' => 'Updated project settings for readiness validation.',
            'status' => 'active',
        ])
        ->assertRedirect(route('committees.manage', [$club, $committee]));

    $committee->refresh();

    expect($committee->name)->toBe('Validation Project Name')
        ->and($committee->description)->toBe('Updated project settings for readiness validation.');
});
