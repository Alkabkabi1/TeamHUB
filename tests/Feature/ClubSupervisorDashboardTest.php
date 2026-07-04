<?php

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Post;
use App\Models\User;

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
            ->has('workspaceProjects')
            ->has('recentActivity')
            ->has('stats')
            ->has('members')
        );
});

test('management dashboard lists members including managers', function () {
    [$supervisor, $club] = managedClub();
    $member = User::factory()->student()->create(['name' => 'Member One']);
    $secondMember = User::factory()->student()->create(['name' => 'Member Two']);

    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->approved()->create([
        'user_id' => $secondMember->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('stats.membersCount', 3)
            ->has('members', 3)
            ->where('members', fn ($members) => collect($members)->contains(
                fn ($row) => $row['email'] === $member->email
            ))
            ->where('members', fn ($members) => collect($members)->contains(
                fn ($row) => $row['email'] === $supervisor->email && in_array('club_lead', $row['roles'], true)
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

test('dashboard includes workspace projects for the club', function () {
    [$supervisor, $club] = managedClub();

    $project = Committee::factory()->create([
        'club_id' => $club->id,
        'name' => 'Demo Project',
    ]);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->has('workspaceProjects', 1)
            ->where('workspaceProjects.0.id', $project->id)
            ->where('stats.projectsCount', 1)
        );
});

test('recent activity includes committee posts', function () {
    [$supervisor, $club] = managedClub();
    $project = Committee::factory()->create(['club_id' => $club->id]);

    $post = Post::factory()->create([
        'club_id' => $club->id,
        'committee_id' => $project->id,
        'title' => 'Project Update',
        'published_at' => now()->subDay(),
    ]);

    Post::factory()->create(['title' => 'Other Club Post']);

    $this->actingAs($supervisor)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('recentActivity', fn ($items) => collect($items)->contains(
                fn ($item) => $item['type'] === 'update' && $item['title'] === $post->title
            ))
        );
});

test('a membership manager only sees member-management capabilities on the dashboard', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $membershipManager = User::factory()->student()->create();
    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $membershipManager->id,
        'club_id' => $club->id,
    ]);
    $membership->syncClubRoles([ClubRole::MembershipManager]);

    $this->actingAs($membershipManager)
        ->get(route('clubs.manage', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clubs/Manage')
            ->where('capabilities', ['manage-members', 'view-reports'])
            ->where('canManageRoles', false)
        );
});
