<?php

namespace App\Http\Controllers;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\Committee;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use App\Services\ClubSupervisorReportService;
use Inertia\Inertia;
use Inertia\Response;

class ClubManagementController extends Controller
{
    public function __construct(
        private readonly ClubSupervisorReportService $reports,
    ) {}

    public function index(Club $club): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageClub($club), 403);

        return Inertia::render('clubs/Manage', $this->managementPayload($club, $user));
    }

    public function members(Club $club): Response
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canManageClub($club), 403);

        $payload = $this->managementPayload($club, $user);

        return Inertia::render('clubs/Members', [
            'theme' => $payload['theme'],
            'club' => $payload['club'],
            'capabilities' => $payload['capabilities'],
            'canManageRoles' => $payload['canManageRoles'],
            'roleOptions' => $payload['roleOptions'],
            'members' => $payload['members'],
            'pendingApplications' => $payload['pendingApplications'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function managementPayload(Club $club, User $user): array
    {
        $club->loadMissing('university:id,name');

        $capabilities = $user->isUniversityStaff()
            ? ClubCapability::values()
            : $user->clubCapabilitiesFor($club)->map(fn (ClubCapability $capability): string => $capability->value)->values()->all();

        $members = $this->reports->clubMembersForManagement($club);
        $projectIds = $club->committees()->pluck('id');

        $workspaceTaskQuery = Task::query()->whereIn('committee_id', $projectIds);
        $workspaceProjects = $club->committees()
            ->withCount([
                'memberships as members_count',
                'tasks as tasks_count',
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', ['done']),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Committee $committee): array => [
                'id' => $committee->id,
                'name' => $committee->name,
                'description' => $committee->description,
                'status' => $committee->status->value,
                'logo_url' => $committee->logo_url,
                'members_count' => $committee->members_count,
                'tasks_count' => $committee->tasks_count,
                'overdue_tasks_count' => $committee->overdue_tasks_count,
            ])
            ->values();

        $recentActivity = collect([
            ...Task::query()
                ->whereIn('committee_id', $projectIds)
                ->with('committee:id,name,club_id')
                ->latest('updated_at')
                ->limit(6)
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => "task-{$task->id}",
                    'type' => 'task',
                    'title' => $task->title,
                    'context' => $task->committee?->name ?? '',
                    'time' => $task->updated_at?->diffForHumans(),
                    'sort_at' => $task->updated_at?->timestamp ?? 0,
                    'url' => route('committees.tasks.show', [$club, $task->committee_id, $task]),
                ]),
            ...Post::query()
                ->whereIn('committee_id', $projectIds)
                ->with('committee:id,name,club_id')
                ->latest('published_at')
                ->limit(4)
                ->get()
                ->map(fn (Post $post): array => [
                    'id' => "post-{$post->id}",
                    'type' => 'update',
                    'title' => $post->title,
                    'context' => $post->committee?->name ?? $club->name,
                    'time' => $post->published_at?->diffForHumans(),
                    'sort_at' => $post->published_at?->timestamp ?? 0,
                    'url' => $post->committee_id
                        ? route('committees.updates.index', [$club, $post->committee_id])
                        : route('clubs.manage', $club),
                ]),
        ])->sortByDesc('sort_at')->take(8)->map(function (array $item): array {
            unset($item['sort_at']);

            return $item;
        })->values();

        return [
            'theme' => ['brand' => $club->theme ?: config('theme.brand')],
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'theme' => $club->theme,
                'logo_url' => $club->logo_url,
                'university' => $club->university?->name,
            ],
            'capabilities' => $capabilities,
            'canManageRoles' => $user->can(ClubCapability::ManageClub->value, $club),
            'roleOptions' => collect(ClubRole::cases())
                ->map(fn (ClubRole $role): array => [
                    'value' => $role->value,
                    'label' => __($role->label()),
                    'isManager' => $role->isManager(),
                ])
                ->values(),
            'stats' => [
                'membersCount' => $members->count(),
                'pendingApplicationsCount' => ClubJoinApplication::query()
                    ->where('club_id', $club->id)
                    ->where('status', 'pending')
                    ->count(),
                'projectsCount' => $workspaceProjects->count(),
                'openTasksCount' => (clone $workspaceTaskQuery)->whereNotIn('status', ['done'])->count(),
            ],
            'workspaceStats' => [
                'projects_count' => $workspaceProjects->count(),
                'tasks_count' => (clone $workspaceTaskQuery)->count(),
                'overdue_tasks_count' => (clone $workspaceTaskQuery)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', ['done'])
                    ->count(),
            ],
            'workspaceProjects' => $workspaceProjects,
            'recentActivity' => $recentActivity,
            'members' => $members,
            'pendingApplications' => ClubJoinApplication::query()
                ->where('club_id', $club->id)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(fn (ClubJoinApplication $application) => [
                    'id' => $application->id,
                    'name' => $application->full_name,
                    'details' => "{$application->major} - {$application->level}",
                    'time' => $application->created_at?->diffForHumans(),
                ])
                ->values(),
        ];
    }
}
