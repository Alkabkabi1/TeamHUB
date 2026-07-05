<?php

use App\Ai\Agents\Assistant;
use App\Ai\Tools\ApproveWorkspaceMembershipRequest;
use App\Ai\Tools\FindProjects;
use App\Ai\Tools\FindWorkspaces;
use App\Ai\Tools\GetMyApplications;
use App\Ai\Tools\GetWorkspaceMembers;
use App\Ai\Tools\GetWorkspacePendingApplications;
use App\Ai\Tools\GetWorkspaceReport;
use App\Ai\Tools\RejectWorkspaceMembershipRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
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
    $workspace = Workspace::factory()->create();
    $student = User::factory()->student()->create();

    $result = decodeTool((new GetWorkspaceMembers($student))->handle(new Request(['workspace' => $workspace->name])));

    expect($result)->toHaveKey('error')
        ->and($result)->not->toHaveKey('members');
});

test('a club supervisor can read their club member roster', function () {
    $workspace = Workspace::factory()->create();
    $supervisor = supervisorForClub($workspace);

    $result = decodeTool((new GetWorkspaceMembers($supervisor))->handle(new Request(['workspace' => $workspace->name])));

    expect($result)->not->toHaveKey('error')
        ->and($result)->toHaveKey('members')
        ->and($result['workspace'])->toBe($workspace->name);
});

test('a plain student cannot read a club report', function () {
    $workspace = Workspace::factory()->create();
    $student = User::factory()->student()->create();

    $result = decodeTool((new GetWorkspaceReport($student))->handle(new Request(['workspace' => $workspace->name, 'type' => 'stats'])));

    expect($result)->toHaveKey('error')
        ->and($result)->not->toHaveKey('report');
});

test('a club supervisor can read their club stats report', function () {
    $workspace = Workspace::factory()->create();
    $supervisor = supervisorForClub($workspace);

    $result = decodeTool((new GetWorkspaceReport($supervisor))->handle(new Request(['workspace' => $workspace->name, 'type' => 'stats'])));

    expect($result)->not->toHaveKey('error')
        ->and($result)->toHaveKey('report')
        ->and($result['report'])->toHaveKey('membersCount');
});

test('FindProjects lists and searches committees by keyword', function () {
    $workspace = Workspace::factory()->create();

    Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'اللجنة التقنية']);
    Project::factory()->create(['workspace_id' => $workspace->id, 'name' => 'اللجنة الثقافية']);

    $student = User::factory()->student()->create();

    $all = decodeTool((new FindProjects($student))->handle(new Request([])));
    expect(collect($all['projects'])->pluck('name'))
        ->toContain('اللجنة التقنية', 'اللجنة الثقافية');

    $filtered = decodeTool((new FindProjects($student))->handle(new Request(['search' => 'تقني'])));
    expect(collect($filtered['projects'])->pluck('name'))
        ->toContain('اللجنة التقنية')
        ->not->toContain('اللجنة الثقافية');
});

test('FindWorkspaces lists active clubs and excludes inactive ones', function () {
    Workspace::factory()->create(['name' => 'نادي نشط', 'status' => 'active']);
    Workspace::factory()->inactive()->create(['name' => 'نادي غير نشط']);

    $student = User::factory()->student()->create();

    $result = decodeTool((new FindWorkspaces($student))->handle(new Request([])));
    $names = collect($result['workspaces'])->pluck('name');

    expect($names)->toContain('نادي نشط')
        ->and($names)->not->toContain('نادي غير نشط');
});

test('GetMyApplications returns the user\'s applications with status', function () {
    $workspace = Workspace::factory()->create();
    $me = User::factory()->student()->create();
    $other = User::factory()->student()->create();

    WorkspaceMembershipRequest::factory()->approved()->create(['user_id' => $me->id, 'workspace_id' => $workspace->id]);
    WorkspaceMembershipRequest::factory()->pending()->create(['user_id' => $other->id, 'workspace_id' => $workspace->id]);

    $result = decodeTool((new GetMyApplications($me))->handle(new Request([])));

    expect($result['applications'])->toHaveCount(1)
        ->and($result['applications'][0]['status'])->toBe('approved')
        ->and($result['applications'][0]['workspace'])->toBe($workspace->name);
});

test('a plain student cannot list a club\'s pending applications', function () {
    $workspace = Workspace::factory()->create();
    $student = User::factory()->student()->create();

    $result = decodeTool((new GetWorkspacePendingApplications($student))->handle(new Request(['workspace' => $workspace->name])));

    expect($result)->toHaveKey('error')
        ->and($result)->not->toHaveKey('applications');
});

test('a club supervisor can list pending applications', function () {
    $workspace = Workspace::factory()->create();
    $supervisor = supervisorForClub($workspace);

    WorkspaceMembershipRequest::factory()->pending()->create(['workspace_id' => $workspace->id]);

    $result = decodeTool((new GetWorkspacePendingApplications($supervisor))->handle(new Request(['workspace' => $workspace->name])));

    expect($result)->not->toHaveKey('error')
        ->and($result['pendingCount'])->toBe(1);
});

test('the pending applications listing surfaces each application id', function () {
    $workspace = Workspace::factory()->create();
    $supervisor = supervisorForClub($workspace);

    $application = WorkspaceMembershipRequest::factory()->pending()->create(['workspace_id' => $workspace->id]);

    $result = decodeTool((new GetWorkspacePendingApplications($supervisor))->handle(new Request(['workspace' => $workspace->name])));

    expect($result['applications'][0]['id'])->toBe($application->id);
});

test('ApproveWorkspaceMembershipRequest resolves a pending application by applicant name', function () {
    $workspace = Workspace::factory()->create();
    $supervisor = supervisorForClub($workspace);
    $applicant = User::factory()->student()->create(['name' => 'هند البقمي']);

    $application = WorkspaceMembershipRequest::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $applicant->id,
    ]);

    $this->actingAs($supervisor);
    $tool = new ApproveWorkspaceMembershipRequest($supervisor);

    // No application_id — resolve by applicant name within the club.
    $result = decodeTool($tool->handle(new Request(['applicant' => 'هند', 'workspace' => $workspace->name])));

    expect($result['status'])->toBe('pending_confirmation')
        ->and($result['summary'])->toContain('هند البقمي');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");
    $outcome = $tool->execute($cached['params']);

    expect($outcome['success'])->toBeTrue()
        ->and($application->fresh()->status)->toBe('approved');
});

test('RejectWorkspaceMembershipRequest resolves a pending application by applicant name', function () {
    $workspace = Workspace::factory()->create();
    $supervisor = supervisorForClub($workspace);
    $applicant = User::factory()->student()->create(['name' => 'سلطان الرشيدي']);

    $application = WorkspaceMembershipRequest::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $applicant->id,
    ]);

    $this->actingAs($supervisor);
    $tool = new RejectWorkspaceMembershipRequest($supervisor);

    $result = decodeTool($tool->handle(new Request(['applicant' => 'سلطان', 'workspace' => $workspace->name])));

    expect($result['status'])->toBe('pending_confirmation')
        ->and($result['summary'])->toContain('سلطان الرشيدي');

    $cached = Cache::get("ai_pending_action:{$result['action_id']}");
    $outcome = $tool->execute($cached['params']);

    expect($outcome['success'])->toBeTrue()
        ->and($application->fresh()->status)->toBe('rejected');
});

test('resolving by applicant name is scoped to the named club', function () {
    $workspace = Workspace::factory()->create();
    $otherClub = Workspace::factory()->create();
    $supervisor = supervisorForClub($workspace);
    $applicant = User::factory()->student()->create(['name' => 'خالد المالكي']);

    // A pending application for the SAME applicant, but in a different club.
    WorkspaceMembershipRequest::factory()->pending()->create([
        'workspace_id' => $otherClub->id,
        'user_id' => $applicant->id,
    ]);

    $this->actingAs($supervisor);

    $result = decodeTool(
        (new ApproveWorkspaceMembershipRequest($supervisor))->handle(new Request(['applicant' => 'خالد', 'workspace' => $workspace->name])),
    );

    // Nothing matches within the supervisor's club, so no action is created.
    expect($result)->toHaveKey('error');
});

test('guests only receive public-data tools', function () {
    $tools = collect((new Assistant(null))->tools())
        ->map(fn ($tool) => class_basename($tool));

    expect($tools)->toContain('GetAppRoutes')
        ->and($tools)->not->toContain('ListMyTasks')
        ->and($tools)->not->toContain('FindTasks')
        ->and($tools)->not->toContain('GetProjectSummary')
        ->and($tools)->not->toContain('CreateTask')
        ->and($tools)->not->toContain('AssignTask');
});

test('students get task read tools and personal status updates but not management write tools', function () {
    $student = User::factory()->student()->create();

    $tools = collect((new Assistant($student))->tools())
        ->map(fn ($tool) => class_basename($tool));

    expect($tools)->toContain('GetAppRoutes', 'ListMyTasks', 'FindTasks', 'GetProjectSummary', 'UpdateTaskStatus')
        ->and($tools)->not->toContain('CreateTask')
        ->and($tools)->not->toContain('AssignTask')
        ->and($tools)->not->toContain('UpdateTaskDetails');
});

test('project managers receive task mutation tools', function () {
    $workspace = Workspace::factory()->create();
    $manager = supervisorForClub($workspace);

    $tools = collect((new Assistant($manager))->tools())
        ->map(fn ($tool) => class_basename($tool));

    expect($tools)->toContain('CreateTask', 'AssignTask', 'UpdateTaskDetails', 'UpdateTaskStatus');
});
