<?php

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use App\Models\VolunteerHour;

/**
 * Create a club managed by a fresh club-lead supervisor and return both.
 *
 * @return array{0: User, 1: Club}
 */
function managedClub(): array
{
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    return [$supervisor, $club];
}

test('guest is redirected when visiting the club management dashboard', function () {
    $club = Club::factory()->create(['status' => 'active']);

    $this->get(route('clubs.manage', $club))
        ->assertRedirect(route('login'));
});

test('a student with no role in the club cannot access its management dashboard', function () {
    [, $club] = managedClub();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('clubs.manage', $club))
        ->assertForbidden();
});

test('a plain member cannot access the management dashboard', function () {
    [, $club] = managedClub();
    $member = User::factory()->student()->create();
    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($member)
        ->get(route('clubs.manage', $club))
        ->assertForbidden();
});

test('a manager of one club cannot manage a different club', function () {
    [$supervisor] = managedClub();
    $otherClub = Club::factory()->create(['status' => 'active']);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $otherClub))
        ->assertForbidden();
});

test('university staff may manage any club', function () {
    $staff = User::factory()->universityStaff()->create();
    $club = Club::factory()->create(['status' => 'active']);

    $this->actingAs($staff)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            // Staff bypass grants the full capability set.
            ->has('capabilities', count(ClubCapability::cases()))
            ->where('canManageRoles', true)
        );
});

test('the management dashboard returns a valid inertia page for a club lead', function () {
    [$supervisor, $club] = managedClub();

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->has('club')
            ->has('capabilities')
            ->where('canManageRoles', true)
            ->has('roleOptions')
            ->has('pastEvents')
            ->has('eligibleAttendees')
            ->has('stats')
            ->has('members')
            ->has('posts')
        );
});

test('management dashboard lists members (including managers) with club volunteer hour totals', function () {
    [$supervisor, $club] = managedClub();
    $memberWithHours = User::factory()->student()->create(['name' => 'Member With Hours']);
    $memberWithoutHours = User::factory()->student()->create(['name' => 'Member Zero Hours']);

    ClubMembership::factory()->approved()->create([
        'user_id' => $memberWithHours->id,
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->approved()->create([
        'user_id' => $memberWithoutHours->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    VolunteerHour::factory()->create([
        'user_id' => $memberWithHours->id,
        'event_id' => $pastEvent->id,
        'hours' => 7.5,
        'approved_by' => $supervisor->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('stats.totalHours', 7.5)
            // The lead and both members are all approved memberships.
            ->where('stats.membersCount', 3)
            ->has('members', 3)
            ->where('members', fn ($members) => collect($members)->contains(
                fn ($member) => $member['email'] === $memberWithHours->email && $member['volunteerHours'] === 7.5
            ))
            ->where('members', fn ($members) => collect($members)->contains(
                fn ($member) => $member['email'] === $supervisor->email && in_array('club_lead', $member['roles'], true)
            ))
        );
});

test('pendingApplicationsCount counts ClubJoinApplication rows not ClubMembership rows', function () {
    [$supervisor, $club] = managedClub();

    ClubJoinApplication::factory()->pending()->count(2)->create([
        'club_id' => $club->id,
    ]);

    ClubJoinApplication::factory()->approved()->create([
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->create([
        'club_id' => $club->id,
        'status' => 'pending',
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('stats.pendingApplicationsCount', 2)
        );
});

test('dashboard passes posts prop with club recent posts ordered latest first', function () {
    [$supervisor, $club] = managedClub();

    $olderPost = Post::factory()->create([
        'club_id' => $club->id,
        'title' => 'Older Post',
        'published_at' => now()->subDays(5),
    ]);

    $newerPost = Post::factory()->create([
        'club_id' => $club->id,
        'title' => 'Newer Post',
        'published_at' => now()->subDay(),
    ]);

    Post::factory()->create(['title' => 'Other Club Post']);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->has('posts', 2)
            ->where('posts.0.id', $newerPost->id)
            ->where('posts.1.id', $olderPost->id)
        );
});

test('a content manager only sees the news capability on the dashboard', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $contentManager = User::factory()->student()->create();
    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $contentManager->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles([ClubRole::ContentManager]);

    $this->actingAs($contentManager)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('capabilities', ['manage-news'])
            ->where('canManageRoles', false)
        );
});
