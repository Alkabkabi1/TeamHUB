<?php

use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\User;
use App\Notifications\JoinApplicationReceivedNotification;
use App\Notifications\MembershipApprovedNotification;
use App\Notifications\MembershipRejectedNotification;
use Illuminate\Support\Facades\Notification;

test('approving a join application notifies the applicant', function () {
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    $supervisor = supervisorForClub($club);
    $student = User::factory()->student()->create();

    $application = ClubJoinApplication::factory()->pending()->create([
        'club_id' => $club->id,
        'user_id' => $student->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('join-applications.approve', $application))
        ->assertRedirect();

    Notification::assertSentTo($student, MembershipApprovedNotification::class);
    Notification::assertNotSentTo($student, MembershipRejectedNotification::class);
});

test('rejecting a join application notifies the applicant', function () {
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    $supervisor = supervisorForClub($club);
    $student = User::factory()->student()->create();

    $application = ClubJoinApplication::factory()->pending()->create([
        'club_id' => $club->id,
        'user_id' => $student->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('join-applications.reject', $application))
        ->assertRedirect();

    Notification::assertSentTo($student, MembershipRejectedNotification::class);
    Notification::assertNotSentTo($student, MembershipApprovedNotification::class);
});

test('a user without the manage-members capability cannot trigger membership notifications', function () {
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    $outsider = User::factory()->student()->create();
    $student = User::factory()->student()->create();

    $application = ClubJoinApplication::factory()->pending()->create([
        'club_id' => $club->id,
        'user_id' => $student->id,
    ]);

    $this->actingAs($outsider)
        ->post(route('join-applications.approve', $application))
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('submitting a join application notifies the club reviewers', function () {
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    $reviewer = supervisorForClub($club);
    $applicant = User::factory()->student()->create([
        'name' => 'وئام راشد',
        'email' => 'applicant@teamhub.test',
    ]);

    $this->actingAs($applicant)
        ->post(route('clubs.join.store', $club), validJoinApplicationPayload($applicant))
        ->assertRedirect(route('clubs.show', $club));

    Notification::assertSentTo($reviewer, JoinApplicationReceivedNotification::class);
    Notification::assertNotSentTo($applicant, JoinApplicationReceivedNotification::class);
});

test('the membership approved notification renders with the branded mail theme', function () {
    $club = Club::factory()->create(['name' => 'Robotics Club']);
    $student = User::factory()->student()->create(['name' => 'Sara']);

    $rendered = (string) (new MembershipApprovedNotification($club))
        ->toMail($student)
        ->render();

    expect($rendered)->toBeString();
    $this->assertStringContainsString('#006471', $rendered);
    $this->assertStringContainsString('teamhub-icon.svg', $rendered);
    $this->assertStringContainsString('Robotics Club', $rendered);
});
