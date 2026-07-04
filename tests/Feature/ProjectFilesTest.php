<?php

use App\Enums\ClubRole;
use App\Enums\CommitteeRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\ClubResource;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function projectFilesLeadAndCommittee(): array
{
    $club = Club::factory()->create(['status' => 'active']);
    $committee = Committee::factory()->create(['club_id' => $club->id]);
    $lead = User::factory()->student()->create();

    $clubMembership = ClubMembership::factory()->approved()->create([
        'user_id' => $lead->id,
        'club_id' => $club->id,
    ]);
    $clubMembership->syncClubRoles([ClubRole::ClubLead]);

    return [$lead, $club, $committee];
}

function projectFilesMember(Club $club, Committee $committee): User
{
    $member = User::factory()->student()->create();

    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);

    $membership = CommitteeMembership::factory()->create([
        'user_id' => $member->id,
        'committee_id' => $committee->id,
    ]);
    $membership->syncCommitteeRoles([CommitteeRole::Member]);

    return $member;
}

test('approved project members can view the project files page', function () {
    [$lead, $club, $committee] = projectFilesLeadAndCommittee();
    $member = projectFilesMember($club, $committee);

    ClubResource::factory()->forCommittee($committee)->create([
        'title' => 'Design brief',
    ]);

    $this->actingAs($member)
        ->get(route('committees.files.index', [$club, $committee]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('committees/Files')
            ->has('files', 1)
            ->where('files.0.title', 'Design brief')
        );
});

test('project leads can upload and delete project files', function () {
    Storage::fake('public');

    [$lead, $club, $committee] = projectFilesLeadAndCommittee();

    $this->actingAs($lead)
        ->post(route('committees.files.store', [$club, $committee]), [
            'title' => 'Sprint assets',
            'description' => 'Shared image pack',
            'type' => ClubResource::TYPE_MEDIA,
            'access' => 'عام',
            'file' => UploadedFile::fake()->image('assets.png'),
        ])
        ->assertRedirect();

    $resource = ClubResource::query()->where('committee_id', $committee->id)->firstOrFail();

    expect($resource->title)->toBe('Sprint assets');
    Storage::disk('public')->assertExists($resource->file_path);

    $this->actingAs($lead)
        ->delete(route('committees.files.destroy', [$club, $committee, $resource]))
        ->assertRedirect();

    $this->assertDatabaseMissing('club_resources', ['id' => $resource->id]);
});
