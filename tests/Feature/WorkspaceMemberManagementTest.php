<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

/**
 * Create an approved membership in $workspace holding the given roles.
 *
 * @param  array<int, WorkspaceRole>  $roles
 */
function membershipWithRoles(Workspace $workspace, array $roles, ?User $user = null): WorkspaceMembership
{
    $user ??= User::factory()->student()->create();

    $membership = WorkspaceMembership::factory()->approved()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ]);

    $membership->syncWorkspaceRoles($roles);

    return $membership;
}

test('a club lead can add an existing student as an approved member', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($workspace, [WorkspaceRole::WorkspaceLead])->user;
    $student = User::factory()->student()->create();

    $this->actingAs($lead)
        ->post(route('workspaces.members.store', $workspace), ['user_id' => $student->id])
        ->assertRedirect();

    $membership = WorkspaceMembership::where('user_id', $student->id)
        ->where('workspace_id', $workspace->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->status)->toBe('approved')
        ->and($membership->hasWorkspaceRole(WorkspaceRole::Member))->toBeTrue();
});

test('a plain member cannot add members', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $member = membershipWithRoles($workspace, [WorkspaceRole::Member])->user;
    $student = User::factory()->student()->create();

    $this->actingAs($member)
        ->post(route('workspaces.members.store', $workspace), ['user_id' => $student->id])
        ->assertForbidden();
});

test('a membership manager cannot grant a manager role when adding a member', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $manager = membershipWithRoles($workspace, [WorkspaceRole::MembershipManager])->user;
    $student = User::factory()->student()->create();

    $this->actingAs($manager)
        ->post(route('workspaces.members.store', $workspace), [
            'user_id' => $student->id,
            'roles' => [WorkspaceRole::MembershipManager->value],
        ])
        ->assertForbidden();
});

test('member search returns matching students excluding existing members', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($workspace, [WorkspaceRole::WorkspaceLead])->user;

    $match = User::factory()->student()->create(['name' => 'Searchable Sara']);
    $existing = membershipWithRoles($workspace, [WorkspaceRole::Member])->user;
    $existing->update(['name' => 'Searchable Sami']);

    $this->actingAs($lead)
        ->getJson(route('workspaces.members.search', ['workspace' => $workspace, 'q' => 'Searchable']))
        ->assertOk()
        ->assertJsonFragment(['id' => $match->id])
        ->assertJsonMissing(['id' => $existing->id]);
});

test('updating member roles requires the manage-workspace capability', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $membershipManager = membershipWithRoles($workspace, [WorkspaceRole::MembershipManager])->user;
    $target = membershipWithRoles($workspace, [WorkspaceRole::Member]);

    $this->actingAs($membershipManager)
        ->put(route('workspaces.members.roles', ['workspace' => $workspace, 'membership' => $target]), [
            'roles' => [WorkspaceRole::MembershipManager->value],
        ])
        ->assertForbidden();
});

test('a club lead can promote a member to a manager role', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($workspace, [WorkspaceRole::WorkspaceLead])->user;
    $target = membershipWithRoles($workspace, [WorkspaceRole::Member]);

    $this->actingAs($lead)
        ->put(route('workspaces.members.roles', ['workspace' => $workspace, 'membership' => $target]), [
            'roles' => [WorkspaceRole::MembershipManager->value],
        ])
        ->assertRedirect();

    $target->refresh();
    expect($target->hasWorkspaceRole(WorkspaceRole::MembershipManager))->toBeTrue()
        ->and($target->hasWorkspaceRole(WorkspaceRole::Member))->toBeTrue();
});

test('the last club lead cannot be demoted', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $leadMembership = membershipWithRoles($workspace, [WorkspaceRole::WorkspaceLead]);
    $lead = $leadMembership->user;

    $this->actingAs($lead)
        ->put(route('workspaces.members.roles', ['workspace' => $workspace, 'membership' => $leadMembership]), [
            'roles' => [],
        ]);

    $leadMembership->refresh();
    expect($leadMembership->hasWorkspaceRole(WorkspaceRole::WorkspaceLead))->toBeTrue();
});

test('the last club lead cannot be removed', function () {
    $staff = User::factory()->universityStaff()->create();
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $leadMembership = membershipWithRoles($workspace, [WorkspaceRole::WorkspaceLead]);

    $this->actingAs($staff)
        ->delete(route('workspaces.members.destroy', ['workspace' => $workspace, 'membership' => $leadMembership]));

    expect(WorkspaceMembership::find($leadMembership->id))->not->toBeNull();
});

test('a club lead can remove a plain member', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($workspace, [WorkspaceRole::WorkspaceLead])->user;
    $target = membershipWithRoles($workspace, [WorkspaceRole::Member]);

    $this->actingAs($lead)
        ->delete(route('workspaces.members.destroy', ['workspace' => $workspace, 'membership' => $target]))
        ->assertRedirect();

    expect(WorkspaceMembership::find($target->id))->toBeNull();
});

test('roles cannot be managed on a membership belonging to another club', function () {
    $workspace = Workspace::factory()->create(['status' => 'active']);
    $otherClub = Workspace::factory()->create(['status' => 'active']);
    $lead = membershipWithRoles($workspace, [WorkspaceRole::WorkspaceLead])->user;
    $foreignMembership = membershipWithRoles($otherClub, [WorkspaceRole::Member]);

    $this->actingAs($lead)
        ->put(route('workspaces.members.roles', ['workspace' => $workspace, 'membership' => $foreignMembership]), [
            'roles' => [WorkspaceRole::MembershipManager->value],
        ])
        ->assertNotFound();
});
