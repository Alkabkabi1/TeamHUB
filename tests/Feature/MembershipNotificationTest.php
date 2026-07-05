<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use App\Notifications\JoinApplicationReceivedNotification;
use App\Notifications\MembershipApprovedNotification;
use App\Notifications\MembershipRejectedNotification;
use Illuminate\Support\Facades\Notification;

test('approving a join application notifies the applicant', function () {
    Notification::fake();

    $workspace = Workspace::factory()->create(['status' => 'active']);
    $supervisor = supervisorForClub($workspace);
    $student = User::factory()->student()->create();

    $application = WorkspaceMembershipRequest::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('workspaces.membership-requests.approve', $application))
        ->assertRedirect();

    Notification::assertSentTo($student, MembershipApprovedNotification::class);
    Notification::assertNotSentTo($student, MembershipRejectedNotification::class);
});

test('rejecting a join application notifies the applicant', function () {
    Notification::fake();

    $workspace = Workspace::factory()->create(['status' => 'active']);
    $supervisor = supervisorForClub($workspace);
    $student = User::factory()->student()->create();

    $application = WorkspaceMembershipRequest::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('workspaces.membership-requests.reject', $application))
        ->assertRedirect();

    Notification::assertSentTo($student, MembershipRejectedNotification::class);
    Notification::assertNotSentTo($student, MembershipApprovedNotification::class);
});

test('a user without the manage-members capability cannot trigger membership notifications', function () {
    Notification::fake();

    $workspace = Workspace::factory()->create(['status' => 'active']);
    $outsider = User::factory()->student()->create();
    $student = User::factory()->student()->create();

    $application = WorkspaceMembershipRequest::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);

    $this->actingAs($outsider)
        ->post(route('workspaces.membership-requests.approve', $application))
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('submitting a join application notifies the club reviewers', function () {
    Notification::fake();

    $workspace = Workspace::factory()->create(['status' => 'active']);
    $reviewer = supervisorForClub($workspace);
    $applicant = User::factory()->student()->create([
        'name' => 'وئام راشد',
        'email' => 'applicant@teamhub.test',
    ]);

    $this->actingAs($applicant)
        ->post(route('workspaces.join.store', $workspace), validJoinApplicationPayload($applicant))
        ->assertRedirect(route('workspaces.show', $workspace));

    Notification::assertSentTo($reviewer, JoinApplicationReceivedNotification::class);
    Notification::assertNotSentTo($applicant, JoinApplicationReceivedNotification::class);
});

test('the membership approved notification renders with the branded mail theme', function () {
    $workspace = Workspace::factory()->create(['name' => 'Robotics Club']);
    $student = User::factory()->student()->create(['name' => 'Sara']);

    $rendered = (string) (new MembershipApprovedNotification($workspace))
        ->toMail($student)
        ->render();

    expect($rendered)->toBeString();
    $this->assertStringContainsString(config('theme.brand'), $rendered);
    $this->assertStringContainsString('teamhub-icon.svg', $rendered);
    $this->assertStringContainsString('Robotics Club', $rendered);
});
