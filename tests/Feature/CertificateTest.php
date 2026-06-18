<?php

use App\Models\Certificate;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// ---------------------------------------------------------------------------
// Store (generate) certificate
// ---------------------------------------------------------------------------

test('guest cannot generate a certificate', function () {
    $attendance = EventAttendance::factory()->checkedIn()->forPastEvent()->create();

    $this->post(route('certificates.store', $attendance))
        ->assertRedirect(route('login'));
});

test('supervisor can generate a certificate for a checked-in past-event attendance', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['name' => 'نادي الحاسبات', 'status' => 'active']);
    $student = User::factory()->student()->create(['name' => 'طالب تجريبي']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    giveClubDefaultTemplate($club);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('certificates.store', $attendance))
        ->assertRedirect();

    $this->assertDatabaseHas('certificates', [
        'event_attendance_id' => $attendance->id,
    ]);

    $cert = Certificate::where('event_attendance_id', $attendance->id)->first();
    expect($cert)->not->toBeNull();
    expect($cert->file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($cert->file_path);
});

test('supervisor can generate a certificate for an approved past-event attendance', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    giveClubDefaultTemplate($club);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $attendance = EventAttendance::factory()->approved()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('certificates.store', $attendance))
        ->assertRedirect();

    $this->assertDatabaseHas('certificates', [
        'event_attendance_id' => $attendance->id,
    ]);
});

test('cannot generate certificate for pending attendance', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    giveClubDefaultTemplate($club);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $attendance = EventAttendance::factory()->pending()->create([
        'event_id' => $pastEvent->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('certificates.store', $attendance))
        ->assertRedirect();

    $this->assertDatabaseMissing('certificates', [
        'event_attendance_id' => $attendance->id,
    ]);
});

test('cannot generate certificate for future event', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $futureEvent = Event::factory()->upcoming()->for($club)->create(['status' => 'active']);

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'event_id' => $futureEvent->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('certificates.store', $attendance))
        ->assertRedirect();

    $this->assertDatabaseMissing('certificates', [
        'event_attendance_id' => $attendance->id,
    ]);
});

test('student cannot generate a certificate (forbidden)', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    $this->actingAs($student)
        ->post(route('certificates.store', $attendance))
        ->assertForbidden();
});

test('generating certificate again updates the file path via updateOrCreate', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    giveClubDefaultTemplate($club);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    $this->actingAs($supervisor)->post(route('certificates.store', $attendance));
    $this->actingAs($supervisor)->post(route('certificates.store', $attendance));

    expect(Certificate::where('event_attendance_id', $attendance->id)->count())->toBe(1);
});

test('cannot generate certificate when the club has no active default template', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    // No template created for this club.
    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('certificates.store', $attendance))
        ->assertRedirect()
        ->assertSessionHas('error', __('certificates.no_template'));

    $this->assertDatabaseMissing('certificates', [
        'event_attendance_id' => $attendance->id,
    ]);
});

// ---------------------------------------------------------------------------
// Download certificate
// ---------------------------------------------------------------------------

test('guest cannot download a certificate', function () {
    $cert = Certificate::factory()->create();

    $this->get(route('certificates.download', $cert))
        ->assertRedirect(route('login'));
});

test('student can download their own certificate', function () {
    Storage::fake('public');

    $student = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $pastEvent = Event::factory()->past()->for($club)->create();

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    Storage::disk('public')->put('certificates/TEST.pdf', '%PDF fake content');

    $cert = Certificate::factory()->create([
        'event_attendance_id' => $attendance->id,
        'file_path' => 'certificates/TEST.pdf',
    ]);

    $response = $this->actingAs($student)
        ->get(route('certificates.download', $cert));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

test('another student gets 403 when trying to download a certificate', function () {
    Storage::fake('public');

    $owner = User::factory()->student()->create();
    $other = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $pastEvent = Event::factory()->past()->for($club)->create();

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $owner->id,
        'event_id' => $pastEvent->id,
    ]);

    Storage::disk('public')->put('certificates/TEST2.pdf', '%PDF fake content');

    $cert = Certificate::factory()->create([
        'event_attendance_id' => $attendance->id,
        'file_path' => 'certificates/TEST2.pdf',
    ]);

    $this->actingAs($other)
        ->get(route('certificates.download', $cert))
        ->assertForbidden();
});

test('supervisor of the club can download a certificate', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $student = User::factory()->student()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $pastEvent = Event::factory()->past()->for($club)->create();

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    Storage::disk('public')->put('certificates/SUPER.pdf', '%PDF supervisor download');

    $cert = Certificate::factory()->create([
        'event_attendance_id' => $attendance->id,
        'file_path' => 'certificates/SUPER.pdf',
    ]);

    $response = $this->actingAs($supervisor)
        ->get(route('certificates.download', $cert));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

test('download regenerates pdf on the fly when file is missing', function () {
    Storage::fake('public');

    $student = User::factory()->student()->create(['name' => 'طالب']);
    $club = Club::factory()->create(['status' => 'active', 'name' => 'نادي']);
    giveClubDefaultTemplate($club);
    $pastEvent = Event::factory()->past()->for($club)->create(['title' => 'فعالية']);

    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    // File path points to a non-existent file
    $cert = Certificate::factory()->create([
        'event_attendance_id' => $attendance->id,
        'file_path' => 'certificates/MISSING.pdf',
    ]);

    Storage::disk('public')->assertMissing('certificates/MISSING.pdf');

    $response = $this->actingAs($student)
        ->get(route('certificates.download', $cert));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

// ---------------------------------------------------------------------------
// Prune command
// ---------------------------------------------------------------------------

test('certificates:prune deletes expired certificates and their files', function () {
    Storage::fake('public');

    $oldAttendance = EventAttendance::factory()->checkedIn()->forPastEvent()->create();
    $recentAttendance = EventAttendance::factory()->checkedIn()->forPastEvent()->create();

    Storage::disk('public')->put('certificates/OLD.pdf', 'old');
    Storage::disk('public')->put('certificates/RECENT.pdf', 'recent');

    $oldCert = Certificate::factory()->create([
        'event_attendance_id' => $oldAttendance->id,
        'file_path' => 'certificates/OLD.pdf',
        'issued_at' => now()->subYear()->subDay(),
    ]);

    $recentCert = Certificate::factory()->create([
        'event_attendance_id' => $recentAttendance->id,
        'file_path' => 'certificates/RECENT.pdf',
        'issued_at' => now()->subMonths(6),
    ]);

    $this->artisan('certificates:prune')->assertSuccessful();

    $this->assertDatabaseMissing('certificates', ['id' => $oldCert->id]);
    $this->assertDatabaseHas('certificates', ['id' => $recentCert->id]);

    Storage::disk('public')->assertMissing('certificates/OLD.pdf');
    Storage::disk('public')->assertExists('certificates/RECENT.pdf');
});

test('certificates:prune reports no expired certificates when none exist', function () {
    Storage::fake('public');

    $attendance = EventAttendance::factory()->checkedIn()->forPastEvent()->create();

    Certificate::factory()->create([
        'event_attendance_id' => $attendance->id,
        'issued_at' => now()->subMonths(3),
    ]);

    $this->artisan('certificates:prune')
        ->assertSuccessful()
        ->expectsOutput('No expired certificates found.');
});

// ---------------------------------------------------------------------------
// Certificate issued notification
// ---------------------------------------------------------------------------

test('issuing a new certificate notifies the student once', function () {
    Storage::fake('public');
    Notification::fake();

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    giveClubDefaultTemplate($club);

    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);
    $attendance = EventAttendance::factory()->checkedIn()->create([
        'user_id' => $student->id,
        'event_id' => $pastEvent->id,
    ]);

    // First issue → notification sent.
    $this->actingAs($supervisor)
        ->post(route('certificates.store', $attendance))
        ->assertRedirect();

    Notification::assertSentToTimes($student, CertificateIssuedNotification::class, 1);

    // Regenerating the same certificate → no second notification.
    $this->actingAs($supervisor)
        ->post(route('certificates.store', $attendance))
        ->assertRedirect();

    Notification::assertSentToTimes($student, CertificateIssuedNotification::class, 1);
});
