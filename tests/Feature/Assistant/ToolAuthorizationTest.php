<?php

use App\Ai\Agents\Assistant;
use App\Ai\Tools\ApproveClubApplication;
use App\Ai\Tools\FindClubs;
use App\Ai\Tools\FindCommittees;
use App\Ai\Tools\GetClubMembers;
use App\Ai\Tools\GetClubPendingApplications;
use App\Ai\Tools\GetClubReport;
use App\Ai\Tools\GetMyApplications;
use App\Ai\Tools\GetMyRegistrations;
use App\Ai\Tools\LogVolunteerHours;
use App\Ai\Tools\RejectClubApplication;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use App\Models\Committee;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;
use App\Models\VolunteerHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Tools\Request;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function decodeTool(string $json): array
{
    return json_decode($json, true);
}

test('a plain student cannot read a club member roster', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();

    $result = decodeTool((new GetClubMembers($student))->handle(new Request(['club' => $club->name])));

    expect($result)->toHaveKey('error')
        ->and($result)->not->toHaveKey('members');
});

test('a club supervisor can read their club member roster', function () {
    $club = Club::factory()->create();
    $supervisor = supervisorForClub($club);

    $result = decodeTool((new GetClubMembers($supervisor))->handle(new Request(['club' => $club->name])));

    expect($result)->not->toHaveKey('error')
        ->and($result)->toHaveKey('members')
        ->and($result['club'])->toBe($club->name);
});

test('a plain student cannot read a club report', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();

    $result = decodeTool((new GetClubReport($student))->handle(new Request(['club' => $club->name, 'type' => 'stats'])));

    expect($result)->toHaveKey('error')
        ->and($result)->not->toHaveKey('report');
});

test('a club supervisor can read their club stats report', function () {
    $club = Club::factory()->create();
    $supervisor = supervisorForClub($club);

    $result = decodeTool((new GetClubReport($supervisor))->handle(new Request(['club' => $club->name, 'type' => 'stats'])));

    expect($result)->not->toHaveKey('error')
        ->and($result)->toHaveKey('report')
        ->and($result['report'])->toHaveKey('membersCount');
});

test('FindCommittees lists and searches committees by keyword', function () {
    $club = Club::factory()->create();

    Committee::factory()->create(['club_id' => $club->id, 'name' => 'اللجنة التقنية']);
    Committee::factory()->create(['club_id' => $club->id, 'name' => 'اللجنة الثقافية']);

    $student = User::factory()->student()->create();

    $all = decodeTool((new FindCommittees($student))->handle(new Request([])));
    expect(collect($all['committees'])->pluck('name'))
        ->toContain('اللجنة التقنية', 'اللجنة الثقافية');

    $filtered = decodeTool((new FindCommittees($student))->handle(new Request(['search' => 'تقني'])));
    expect(collect($filtered['committees'])->pluck('name'))
        ->toContain('اللجنة التقنية')
        ->not->toContain('اللجنة الثقافية');
});

test('FindClubs lists active clubs and excludes inactive ones', function () {
    Club::factory()->create(['name' => 'نادي نشط', 'status' => 'active']);
    Club::factory()->inactive()->create(['name' => 'نادي غير نشط']);

    $student = User::factory()->student()->create();

    $result = decodeTool((new FindClubs($student))->handle(new Request([])));
    $names = collect($result['clubs'])->pluck('name');

    expect($names)->toContain('نادي نشط')
        ->and($names)->not->toContain('نادي غير نشط');
});

test('GetMyApplications returns the user\'s applications with status', function () {
    $club = Club::factory()->create();
    $me = User::factory()->student()->create();
    $other = User::factory()->student()->create();

    ClubJoinApplication::factory()->approved()->create(['user_id' => $me->id, 'club_id' => $club->id]);
    ClubJoinApplication::factory()->pending()->create(['user_id' => $other->id, 'club_id' => $club->id]);

    $result = decodeTool((new GetMyApplications($me))->handle(new Request([])));

    expect($result['applications'])->toHaveCount(1)
        ->and($result['applications'][0]['status'])->toBe('approved')
        ->and($result['applications'][0]['club'])->toBe($club->name);
});

test('a plain student cannot list a club\'s pending applications', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();

    $result = decodeTool((new GetClubPendingApplications($student))->handle(new Request(['club' => $club->name])));

    expect($result)->toHaveKey('error')
        ->and($result)->not->toHaveKey('applications');
});

test('a club supervisor can list pending applications', function () {
    $club = Club::factory()->create();
    $supervisor = supervisorForClub($club);

    ClubJoinApplication::factory()->pending()->create(['club_id' => $club->id]);

    $result = decodeTool((new GetClubPendingApplications($supervisor))->handle(new Request(['club' => $club->name])));

    expect($result)->not->toHaveKey('error')
        ->and($result['pendingCount'])->toBe(1);
});

test('the pending applications listing surfaces each application id', function () {
    $club = Club::factory()->create();
    $supervisor = supervisorForClub($club);

    $application = ClubJoinApplication::factory()->pending()->create(['club_id' => $club->id]);

    $result = decodeTool((new GetClubPendingApplications($supervisor))->handle(new Request(['club' => $club->name])));

    expect($result['applications'][0]['id'])->toBe($application->id);
});

test('ApproveClubApplication resolves a pending application by applicant name', function () {
    $club = Club::factory()->create();
    $supervisor = supervisorForClub($club);
    $applicant = User::factory()->student()->create(['name' => 'هند البقمي']);

    $application = ClubJoinApplication::factory()->pending()->create([
        'club_id' => $club->id,
        'user_id' => $applicant->id,
    ]);

    $this->actingAs($supervisor);
    $tool = new ApproveClubApplication($supervisor);

    // No application_id — resolve by applicant name within the club.
    $result = decodeTool($tool->handle(new Request(['applicant' => 'هند', 'club' => $club->name])));

    expect($result['status'])->toBe('pending_confirmation')
        ->and($result['summary'])->toContain('هند البقمي');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");
    $outcome = $tool->execute($cached['params']);

    expect($outcome['success'])->toBeTrue()
        ->and($application->fresh()->status)->toBe('approved');
});

test('RejectClubApplication resolves a pending application by applicant name', function () {
    $club = Club::factory()->create();
    $supervisor = supervisorForClub($club);
    $applicant = User::factory()->student()->create(['name' => 'سلطان الرشيدي']);

    $application = ClubJoinApplication::factory()->pending()->create([
        'club_id' => $club->id,
        'user_id' => $applicant->id,
    ]);

    $this->actingAs($supervisor);
    $tool = new RejectClubApplication($supervisor);

    $result = decodeTool($tool->handle(new Request(['applicant' => 'سلطان', 'club' => $club->name])));

    expect($result['status'])->toBe('pending_confirmation')
        ->and($result['summary'])->toContain('سلطان الرشيدي');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");
    $outcome = $tool->execute($cached['params']);

    expect($outcome['success'])->toBeTrue()
        ->and($application->fresh()->status)->toBe('rejected');
});

test('resolving by applicant name is scoped to the named club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();
    $supervisor = supervisorForClub($club);
    $applicant = User::factory()->student()->create(['name' => 'خالد المالكي']);

    // A pending application for the SAME applicant, but in a different club.
    ClubJoinApplication::factory()->pending()->create([
        'club_id' => $otherClub->id,
        'user_id' => $applicant->id,
    ]);

    $this->actingAs($supervisor);

    $result = decodeTool(
        (new ApproveClubApplication($supervisor))->handle(new Request(['applicant' => 'خالد', 'club' => $club->name])),
    );

    // Nothing matches within the supervisor's club, so no action is created.
    expect($result)->toHaveKey('error');
});

test('LogVolunteerHours can award general hours without an event', function () {
    $club = Club::factory()->create();
    $supervisor = supervisorForClub($club);
    $member = User::factory()->student()->create(['name' => 'ريم العتيبي']);

    ClubMembership::factory()->approved()->create([
        'user_id' => $member->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor);
    $tool = new LogVolunteerHours($supervisor);

    // No event named — the assistant logs general (activity-less) hours.
    $result = decodeTool($tool->handle(new Request([
        'club' => $club->name,
        'user' => 'ريم',
        'hours' => 5,
    ])));

    expect($result['status'])->toBe('pending_confirmation')
        ->and($result['summary'])->toContain('ريم العتيبي');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");

    expect($cached['params']['event_id'])->toBeNull()
        ->and($cached['params']['club_id'])->toBe($club->id);

    $outcome = $tool->execute($cached['params']);

    expect($outcome['success'])->toBeTrue();

    $record = VolunteerHour::query()
        ->where('user_id', $member->id)
        ->where('club_id', $club->id)
        ->first();

    expect($record)->not->toBeNull()
        ->and($record->event_id)->toBeNull()
        ->and((float) $record->hours)->toBe(5.0);
});

test('guests only receive public-data tools', function () {
    $tools = collect((new Assistant(null))->tools())
        ->map(fn ($tool) => class_basename($tool));

    expect($tools)->toContain('SearchCatalog', 'FindClubs', 'FindEvents', 'FindCommittees', 'FindResources', 'ListNews', 'GetClubInfo', 'GetCommitteeInfo', 'GetEventDetails')
        ->and($tools)->not->toContain('GetMyRegistrations')
        ->and($tools)->not->toContain('GetMyApplications')
        ->and($tools)->not->toContain('GetClubMembers')
        ->and($tools)->not->toContain('GetClubPendingApplications');
});

test('students get personal tools but not management tools', function () {
    $student = User::factory()->student()->create();

    $tools = collect((new Assistant($student))->tools())
        ->map(fn ($tool) => class_basename($tool));

    expect($tools)->toContain('GetMyRegistrations', 'GetMyCertificates', 'GetMyApplications')
        ->and($tools)->not->toContain('GetClubMembers')
        ->and($tools)->not->toContain('GetClubReport')
        ->and($tools)->not->toContain('GetClubPendingApplications');
});

test('my registrations are scoped to the current user only', function () {
    $club = Club::factory()->create();

    $mine = Event::factory()->create(['club_id' => $club->id, 'title' => 'My Event']);
    $theirs = Event::factory()->create(['club_id' => $club->id, 'title' => 'Their Event']);

    $me = User::factory()->student()->create();
    $other = User::factory()->student()->create();

    EventAttendance::factory()->create(['user_id' => $me->id, 'event_id' => $mine->id]);
    EventAttendance::factory()->create(['user_id' => $other->id, 'event_id' => $theirs->id]);

    $result = decodeTool((new GetMyRegistrations($me))->handle(new Request([])));

    $titles = collect($result['registrations'])->pluck('event')->all();

    expect($titles)->toContain('My Event')
        ->and($titles)->not->toContain('Their Event');
});
