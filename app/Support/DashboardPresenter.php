<?php

namespace App\Support;

use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;

class DashboardPresenter
{
    public function __construct(
        private DashboardData $dashboardData,
        private ProjectPresenter $projectPresenter,
        private TaskPresenter $taskPresenter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, ?string $persona, ?int $activeProjectId = null): array
    {
        return match ($persona) {
            'admin' => ['panel' => $this->adminPanel($user)],
            'workspace_lead' => ['panel' => $this->workspaceLeadPanel($user)],
            'project_leader' => ['panel' => $this->leaderPanel($user, $activeProjectId)],
            default => ['panel' => $this->defaultPanel($user, $activeProjectId)],
        };
    }

    public function greeting(User $user, ?string $persona): string
    {
        if ($persona !== null) {
            return __('dashboard.persona_greeting', [
                'role' => __("auth.demo_roles.{$persona}"),
            ]);
        }

        return __('dashboard.greeting', ['name' => $user->name]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPanel(User $user, ?int $activeProjectId): array
    {
        if ($user->isAdmin()) {
            return $this->adminPanel($user);
        }

        if ($user->managedProjects()->isNotEmpty()) {
            return $this->leaderPanel($user, $activeProjectId);
        }

        if ($user->managedWorkspaces()->isNotEmpty()) {
            return $this->workspaceLeadPanel($user);
        }

        return $this->legacyPanel($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminPanel(User $user): array
    {
        $projectIds = $this->dashboardData->accessibleProjectIds($user);

        $leaders = User::query()
            ->whereIn('email', collect(DemoRoles::accounts())->where('role', 'project_leader')->pluck('email'))
            ->get(['id', 'name', 'email']);

        $workspaceLeaders = User::query()
            ->whereIn('email', collect(DemoRoles::accounts())->where('role', 'workspace_lead')->pluck('email'))
            ->get(['id', 'name', 'email']);

        $workspaceOptions = DemoWorkspace::options();

        $managedWorkspaces = Workspace::query()
            ->whereIn('id', collect($workspaceOptions)->pluck('id'))
            ->with([
                'memberships' => fn ($query) => $query
                    ->where('status', 'approved')
                    ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('role', WorkspaceRole::WorkspaceLead->value))
                    ->with('user:id,name,email'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Workspace $workspace): array {
                /** @var WorkspaceMembership|null $leadMembership */
                $leadMembership = $workspace->memberships->first();

                return [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'leader' => $leadMembership?->user ? [
                        'id' => $leadMembership->user->id,
                        'name' => $leadMembership->user->name,
                        'email' => $leadMembership->user->email,
                    ] : null,
                    'url' => route('workspaces.manage', $workspace, absolute: false),
                ];
            })
            ->values()
            ->all();

        $projects = Project::query()
            ->whereIn('id', $projectIds)
            ->with(['workspace:id,name', 'memberships.user:id,name,email', 'memberships.roles'])
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Done),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Project $project): array {
                return $this->projectPresenter->adminListItem(
                    $project,
                    $this->projectLeader($project),
                    $this->progressPercent($project),
                    (int) $project->tasks_count,
                );
            })
            ->values()
            ->all();

        return [
            'type' => 'admin',
            'projects' => $projects,
            'leaders' => $leaders->map(fn (User $leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
                'email' => $leader->email,
            ])->values()->all(),
            'workspace_leaders' => $workspaceLeaders->map(fn (User $leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
                'email' => $leader->email,
            ])->values()->all(),
            'managed_workspaces' => $managedWorkspaces,
            'workspaces' => $workspaceOptions,
            'stats' => [
                'projects' => count($projects),
                'leaders' => $leaders->count(),
                'workspace_leaders' => $workspaceLeaders->count(),
                'open_tasks' => Task::query()
                    ->whereIn('project_id', $projectIds)
                    ->where('status', '!=', TaskStatus::Done)
                    ->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceLeadPanel(User $user): array
    {
        $workspace = $user->managedWorkspace();

        if ($workspace === null) {
            return [
                'type' => 'workspace_lead',
                'workspace' => null,
                'manage_url' => null,
                'projects' => [],
                'pending_requests' => 0,
                'stats' => ['projects' => 0, 'members' => 0, 'pending_requests' => 0],
            ];
        }

        $workspace->loadCount([
            'memberships as members_count',
            'projects as projects_count',
        ]);

        $pendingRequests = WorkspaceMembershipRequest::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->count();

        $projects = Project::query()
            ->where('workspace_id', $workspace->id)
            ->with(['memberships.user:id,name,email', 'memberships.roles'])
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Done),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Project $project): array {
                return $this->projectPresenter->adminListItem(
                    $project,
                    $this->projectLeader($project),
                    $this->progressPercent($project),
                    (int) $project->tasks_count,
                );
            })
            ->values()
            ->all();

        return [
            'type' => 'workspace_lead',
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ],
            'manage_url' => route('workspaces.manage', $workspace, absolute: false),
            'projects' => $projects,
            'pending_requests' => $pendingRequests,
            'stats' => [
                'projects' => (int) $workspace->projects_count,
                'members' => (int) $workspace->members_count,
                'pending_requests' => $pendingRequests,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leaderPanel(User $user, ?int $activeProjectId = null): array
    {
        $managed = $user->managedProjects();

        if ($managed->isEmpty()) {
            $managed = $this->dashboardData->projectsQuery($user)->get();
        }

        $projectOptions = $managed->map(function (Project $project): array {
            $project->loadMissing(['workspace:id,name']);

            return [
                'id' => $project->id,
                'title' => $project->name,
                'workspace' => $project->workspace?->name ?? '',
            ];
        })->values()->all();

        $project = $activeProjectId
            ? $managed->firstWhere('id', $activeProjectId) ?? $managed->first()
            : $managed->first();

        if ($project === null) {
            return [
                'type' => 'project_leader',
                'project' => null,
                'projects' => [],
                'active_project_id' => null,
                'team' => [],
                'review_queue' => [],
                'members' => [],
                'task_store_url' => null,
                'tasks_index_url' => null,
            ];
        }

        $project->load(['workspace:id,name', 'memberships.user:id,name', 'memberships.roles']);

        $tasks = Task::query()
            ->forProject($project)
            ->with('assignee:id,name')
            ->limit(50)
            ->get();

        $team = $project->memberships
            ->filter(fn (ProjectMembership $membership) => $membership->user_id !== $user->id)
            ->map(function (ProjectMembership $membership) use ($tasks): array {
                $memberTasks = $tasks->where('assigned_to', $membership->user_id);
                $done = $memberTasks->where('status', TaskStatus::Done)->count();

                return [
                    'id' => $membership->user_id,
                    'name' => $membership->user?->name ?? __('dashboard.unassigned'),
                    'initials' => mb_strtoupper(mb_substr((string) ($membership->user?->name ?? '?'), 0, 1)),
                    'tasks_total' => $memberTasks->count(),
                    'tasks_done' => $done,
                    'progress' => $memberTasks->count() > 0
                        ? (int) round(($done / $memberTasks->count()) * 100)
                        : 0,
                ];
            })
            ->values()
            ->all();

        $reviewQueue = $tasks
            ->where('status', TaskStatus::Review)
            ->take(10)
            ->map(fn (Task $task): array => $this->taskPresenter->leaderReviewItem($task))
            ->values()
            ->all();

        $members = $project->memberships
            ->map(fn (ProjectMembership $membership): array => [
                'id' => $membership->user_id,
                'name' => $membership->user?->name ?? '',
            ])
            ->filter(fn (array $member) => $member['id'] !== $user->id)
            ->values()
            ->all();

        $openTasks = $tasks->where('status', '!=', TaskStatus::Done)->count();

        return [
            'type' => 'project_leader',
            'projects' => $projectOptions,
            'active_project_id' => $project->id,
            'project' => $this->projectPresenter->leaderSummary($project, $openTasks),
            'team' => $team,
            'review_queue' => $reviewQueue,
            'members' => $members,
            'open_tasks' => $openTasks,
            'task_store_url' => route('projects.tasks.store', [$project->workspace_id, $project], absolute: false),
            'tasks_index_url' => route('projects.tasks.index', [$project->workspace_id, $project], absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyPanel(User $user): array
    {
        return [
            'type' => 'legacy',
            'roleContext' => $this->dashboardData->roleContext($user),
            'kpis' => $this->dashboardData->kpis($user),
            'projects' => $this->dashboardData->projectsQuery($user)->limit(4)->get()
                ->map(fn (Project $project) => $this->dashboardData->presentProject($project))
                ->all(),
            'tasks' => $this->dashboardData->tasksQuery($user)->dueToday()->limit(8)->get()
                ->map(fn (Task $task) => $this->dashboardData->presentTask($task))
                ->all(),
        ];
    }

    private function projectLeader(Project $project): ?User
    {
        /** @var ProjectMembership|null $membership */
        $membership = $project->memberships
            ->first(fn (ProjectMembership $membership) => $membership->roles
                ->pluck('role')
                ->contains(fn (ProjectRole $role): bool => $role->isManager()));

        return $membership?->user;
    }

    private function progressPercent(Project $project): int
    {
        $total = (int) ($project->tasks_count ?? 0);
        $done = (int) ($project->done_tasks_count ?? 0);

        return $total > 0 ? (int) round(($done / $total) * 100) : 0;
    }
}
