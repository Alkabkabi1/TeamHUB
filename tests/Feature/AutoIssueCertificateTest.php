<?php

use App\Enums\ClubRole;
use App\Models\Certificate;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function makeAutoIssueScanner(Club $club): User
{
    $scanner = User::factory()->student()->create();

    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $scanner->id,
        'club_id' => $club->id,
    ]);
    $membership->assignClubRole(ClubRole::AttendanceScanner);

    return $scanner;
}

// ---------------------------------------------------------------------------
// Scheduled sweep: certificates:issue-due
// ---------------------------------------------------------------------------

test('issue-due generates certificates for checked-in attendees of ended events', function () {
    Storage::fake('public');
    Notification::fake();

    $club = Club::factory()->create(['status' => 'active']);
    giveClubDefaultTemplate($club);
    $event = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $student = User::factory()->student()->create();
    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
    ]);

    $this->artisan('certificates:issue-due')->assertSuccessful();

    $this->assertDatabaseHas('certificates', [
        'user_id' => $student->id,
        'event_id' => $event->id,
    ]);
    Notification::assertSentToTimes($student, CertificateIssuedNotification::class, 1);
});

test('issue-due is idempotent and never duplicates a certificate', function () {
    Storage::fake('public');

    $club = Club::factory()->create(['status' => 'active']);
    giveClubDefaultTemplate($club);
    $event = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $student = User::factory()->student()->create();
    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
    ]);

    $this->artisan('certificates:issue-due')->assertSuccessful();
    $this->artisan('certificates:issue-due')->assertSuccessful();

    expect(Certificate::query()->where('event_id', $event->id)->count())->toBe(1);
});

test('issue-due skips future events', function () {
    Storage::fake('public');

    $club = Club::factory()->create(['status' => 'active']);
    giveClubDefaultTemplate($club);
    $event = Event::factory()->upcoming()->for($club)->create(['status' => 'active']);

    $student = User::factory()->student()->create();
    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
    ]);

    $this->artisan('certificates:issue-due')->assertSuccessful();

    $this->assertDatabaseMissing('certificates', ['user_id' => $student->id]);
});

test('issue-due skips clubs without an active default template', function () {
    Storage::fake('public');

    $club = Club::factory()->create(['status' => 'active']);
    $event = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $student = User::factory()->student()->create();
    EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
    ]);

    $this->artisan('certificates:issue-due')->assertSuccessful();

    $this->assertDatabaseMissing('certificates', ['user_id' => $student->id]);
});

// ---------------------------------------------------------------------------
// Immediate issuance on check-in (event already ended)
// ---------------------------------------------------------------------------

test('checking in a student to an already-ended event issues a certificate immediately', function () {
    Storage::fake('public');

    $club = Club::factory()->create(['status' => 'active']);
    giveClubDefaultTemplate($club);
    $event = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $scanner = makeAutoIssueScanner($club);
    $student = User::factory()->student()->create();

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertOk();

    $this->assertDatabaseHas('certificates', [
        'user_id' => $student->id,
        'event_id' => $event->id,
    ]);
});

test('checking in to a future event does not issue a certificate yet', function () {
    Storage::fake('public');

    $club = Club::factory()->create(['status' => 'active']);
    giveClubDefaultTemplate($club);
    $event = Event::factory()->upcoming()->for($club)->create(['status' => 'active']);

    $scanner = makeAutoIssueScanner($club);
    $student = User::factory()->student()->create();

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertOk();

    $this->assertDatabaseMissing('certificates', ['user_id' => $student->id]);
});
