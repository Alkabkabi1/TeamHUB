<?php

use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeScannerForClub(Club $club): User
{
    $scanner = User::factory()->student()->create();

    $membership = ClubMembership::factory()->approved()->create([
        'user_id' => $scanner->id,
        'club_id' => $club->id,
    ]);
    $membership->assignClubRole(ClubRole::AttendanceScanner);

    return $scanner;
}

function registerStudentFor(Event $event): User
{
    $student = User::factory()->student()->create();
    EventAttendance::factory()->create([
        'user_id' => $student->id,
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    return $student;
}

// ─── Authorization ────────────────────────────────────────────────────────────

test('attendance scanner can open the scan page', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $event = Event::factory()->for($club)->create();

    $this->actingAs($scanner)
        ->get(route('events.scan', [$club, $event]))
        ->assertOk();
});

test('a plain member without the scanner role gets 403', function () {
    $club = Club::factory()->create();
    $member = User::factory()->student()->create();
    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);
    $event = Event::factory()->for($club)->create();
    $student = registerStudentFor($event);

    $this->actingAs($member)
        ->get(route('events.scan', [$club, $event]))
        ->assertForbidden();

    $this->actingAs($member)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertForbidden();
});

test('scanning an event from another club returns 404', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $foreignEvent = Event::factory()->for($otherClub)->create();

    $this->actingAs($scanner)
        ->get(route('events.scan', [$club, $foreignEvent]))
        ->assertNotFound();
});

// ─── Scan shortcut on the public event page ──────────────────────────────────

test('the event detail page exposes canScan to a scanner for a live activity', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $event = Event::factory()->upcoming()->for($club)->create(['status' => 'active']);

    $this->actingAs($scanner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('EventDetailPage')
            ->where('canScan', true)
        );
});

test('canScan is false for a finished activity and for non-scanners', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $pastEvent = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $this->actingAs($scanner)
        ->get(route('events.show', $pastEvent))
        ->assertInertia(fn ($page) => $page->where('canScan', false));

    $student = User::factory()->student()->create();
    $liveEvent = Event::factory()->upcoming()->for($club)->create(['status' => 'active']);

    $this->actingAs($student)
        ->get(route('events.show', $liveEvent))
        ->assertInertia(fn ($page) => $page->where('canScan', false));
});

// ─── Check-in ──────────────────────────────────────────────────────────────

test('scanning a registered student logs todays attendance and marks them checked in', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $event = Event::factory()->for($club)->create();
    $student = registerStudentFor($event);

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertOk()
        ->assertJson(['result' => 'checked_in', 'studentName' => $student->name, 'daysAttended' => 1]);

    $attendance = EventAttendance::query()
        ->where('user_id', $student->id)
        ->where('event_id', $event->id)
        ->first();

    expect($attendance->status)->toBe('checked_in');

    $checkin = $attendance->checkins()->whereDate('attended_on', now()->toDateString())->first();
    expect($checkin)->not->toBeNull();
    expect($checkin->recorded_by)->toBe($scanner->id);
});

test('scanning the same student twice in one day is idempotent', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $event = Event::factory()->for($club)->create();
    $student = registerStudentFor($event);

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertJson(['result' => 'checked_in']);

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertJson(['result' => 'already_today', 'daysAttended' => 1]);

    expect(EventAttendance::query()->first()->checkins()->count())->toBe(1);
});

test('a multi-day activity accrues one check-in per day', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $event = Event::factory()->for($club)->create();
    $student = registerStudentFor($event);

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertJson(['result' => 'checked_in', 'daysAttended' => 1]);

    $this->travel(1)->days();

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $student->qr_token])
        ->assertJson(['result' => 'checked_in', 'daysAttended' => 2]);

    expect(EventAttendance::query()->first()->checkins()->count())->toBe(2);
});

test('scanning an unregistered student admits them as a walk-in', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $event = Event::factory()->for($club)->create();
    $stranger = User::factory()->student()->create();

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => $stranger->qr_token])
        ->assertOk()
        ->assertJson([
            'result' => 'checked_in',
            'studentName' => $stranger->name,
            'wasWalkIn' => true,
            'daysAttended' => 1,
        ]);

    // A registration row is created on the fly and checked in.
    $attendance = EventAttendance::query()
        ->where('user_id', $stranger->id)
        ->where('event_id', $event->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->status)->toBe('checked_in');
    expect($attendance->checkins()->count())->toBe(1);
});

test('scanning an unknown token is rejected', function () {
    $club = Club::factory()->create();
    $scanner = makeScannerForClub($club);
    $event = Event::factory()->for($club)->create();

    $this->actingAs($scanner)
        ->postJson(route('events.checkin', [$club, $event]), ['qr_token' => 'not-a-real-token'])
        ->assertJson(['result' => 'invalid']);

    $this->assertDatabaseEmpty('attendance_checkins');
});
