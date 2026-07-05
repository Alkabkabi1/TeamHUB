<?php

namespace App\Support;

use App\Enums\TaskActivityType;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardData
{
    /** @var list<string> */
    private const PROJECT_COLORS = ['#7c3aed', '#16a34a', '#2563eb', '#c8924a', '#dc2626', '#0891b2'];

    private ?int $cachedUserId = null;

    /** @var Collection<int, int|string>|null */
    private ?Collection $accessibleProjectIdsCache = null;

    public function __construct(
        private ProjectPresenter $projectPresenter,
        private TaskPresenter $taskPresenter,
    ) {}

    public function accessibleProjectIds(User $user): Collection
    {
        if ($this->cachedUserId === $user->id && $this->accessibleProjectIdsCache !== null) {
            return $this->accessibleProjectIdsCache;
        }

        if ($user->isAdmin()) {
            $ids = Project::query()->pluck('id');
        } else {
            $managedWorkspaceIds = $user->managedWorkspaces()->pluck('id');

            $membershipIds = $user->projectMemberships()
                ->where('status', 'approved')
                ->pluck('project_id');

            $inheritedIds = $managedWorkspaceIds->isNotEmpty()
                ? Project::query()->whereIn('workspace_id', $managedWorkspaceIds)->pluck('id')
                : collect();

            $ids = $membershipIds->merge($inheritedIds)->unique()->values();
        }

        $this->cachedUserId = $user->id;
        $this->accessibleProjectIdsCache = $ids;

        return $ids;
    }

    /**
     * @return Collection<int, Workspace>
     */
    public function accessibleWorkspaces(User $user): Collection
    {
        if ($user->isAdmin()) {
            return Workspace::query()->orderBy('name')->get();
        }

        $managed = $user->managedWorkspaces();

        $memberWorkspaceIds = $user->workspaceMemberships()
            ->where('status', 'approved')
            ->pluck('workspace_id');

        $memberWorkspaces = $memberWorkspaceIds->isNotEmpty()
            ? Workspace::query()->whereIn('id', $memberWorkspaceIds)->get()
            : collect();

        return $managed->merge($memberWorkspaces)->unique('id')->sortBy('name')->values();
    }

    /**
     * @return Builder<Project>
     */
    public function projectsQuery(User $user, ?int $workspaceId = null): Builder
    {
        $ids = $this->accessibleProjectIds($user);

        return Project::query()
            ->whereIn('id', $ids)
            ->when($workspaceId, fn (Builder $query) => $query->where('workspace_id', $workspaceId))
            ->with(['workspace:id,name,theme', 'memberships.user:id,name'])
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn (Builder $query) => $query->where('status', TaskStatus::Done),
                'memberships as members_count' => fn (Builder $query) => $query->where('status', 'approved'),
            ])
            ->orderBy('name');
    }

    /**
     * @return Builder<Task>
     */
    public function tasksQuery(User $user, ?int $workspaceId = null): Builder
    {
        $projectIds = $this->accessibleProjectIds($user);

        return Task::query()
            ->whereIn('project_id', $projectIds)
            ->when($workspaceId, fn (Builder $query) => $query->whereHas(
                'project',
                fn (Builder $project) => $project->where('workspace_id', $workspaceId),
            ))
            ->withDashboardListRelations();
    }

    /**
     * @return array<string, mixed>
     */
    public function presentProject(Project $project): array
    {
        return $this->projectPresenter->card($project);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentTask(Task $task): array
    {
        return $this->taskPresenter->listItem($task);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function kpis(User $user): array
    {
        $projectIds = $this->accessibleProjectIds($user);

        $totalProjects = Project::query()->whereIn('id', $projectIds)->count();

        $statusCounts = Task::query()
            ->whereIn('project_id', $projectIds)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $overdue = Task::query()
            ->whereIn('project_id', $projectIds)
            ->overdue()
            ->count();

        $inProgress = (int) ($statusCounts[TaskStatus::InProgress->value] ?? 0);
        $done = (int) ($statusCounts[TaskStatus::Done->value] ?? 0);
        $review = (int) ($statusCounts[TaskStatus::Review->value] ?? 0);

        return [
            [
                'id' => 'projects',
                'label' => __('dashboard.kpis.projects'),
                'value' => $totalProjects,
                'trend' => '',
                'trendUp' => true,
                'icon' => 'projects',
            ],
            [
                'id' => 'overdue',
                'label' => __('dashboard.kpis.overdue'),
                'value' => $overdue,
                'trend' => '',
                'trendUp' => false,
                'icon' => 'overdue',
            ],
            [
                'id' => 'progress',
                'label' => __('dashboard.kpis.in_progress'),
                'value' => $inProgress,
                'trend' => '',
                'trendUp' => true,
                'icon' => 'progress',
            ],
            [
                'id' => 'done',
                'label' => __('dashboard.kpis.done'),
                'value' => $done,
                'trend' => $review > 0 ? (string) $review : '',
                'trendUp' => true,
                'icon' => 'done',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activities(User $user, int $limit = 8): array
    {
        $projectIds = $this->accessibleProjectIds($user);

        return TaskActivity::query()
            ->whereHas('task', fn (Builder $query) => $query->whereIn('project_id', $projectIds))
            ->with(['user:id,name', 'task:id,title,project_id', 'task.project:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (TaskActivity $activity): array {
                $userName = $activity->user?->name ?? __('dashboard.system');
                $target = $activity->task?->title ?? '';

                return [
                    'id' => $activity->id,
                    'user' => $userName,
                    'initials' => $this->initials($userName),
                    'action' => $activity->message(),
                    'target' => $target,
                    'time' => $activity->created_at?->locale(app()->getLocale())->diffForHumans() ?? '',
                    'type' => match ($activity->type) {
                        TaskActivityType::CommentAdded => 'comment',
                        TaskActivityType::TaskAssigned => 'assign',
                        TaskActivityType::DeliverableSubmitted => 'upload',
                        TaskActivityType::DeliverableApproved, TaskActivityType::ChangesRequested => 'complete',
                        default => 'comment',
                    },
                ];
            })
            ->all();
    }

    /**
     * @return list<array{date: string, title: string, type: string}>
     */
    public function calendarMarkers(User $user): array
    {
        $projectIds = $this->accessibleProjectIds($user);
        $markers = [];

        Task::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('due_at')
            ->select(['id', 'title', 'due_at'])
            ->get()
            ->each(function (Task $task) use (&$markers): void {
                $markers[] = [
                    'date' => $task->due_at->toDateString(),
                    'title' => $task->title,
                    'type' => 'task',
                ];
            });

        return $markers;
    }

    /**
     * @return list<array{id: int, name: string, letter: string, color: string, url: string}>
     */
    public function workspaces(User $user): array
    {
        return $this->accessibleWorkspaces($user)
            ->map(function (Workspace $workspace): array {
                $letter = mb_substr($workspace->name, 0, 1);

                return [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'letter' => $letter,
                    'color' => self::PROJECT_COLORS[$workspace->id % count(self::PROJECT_COLORS)],
                    'url' => route('projects', ['workspace' => $workspace->id], absolute: false),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, create_url: string}>
     */
    public function creatableWorkspaces(User $user): array
    {
        $workspaces = $user->isAdmin()
            ? Workspace::query()->orderBy('name')->get()
            : $user->managedWorkspaces();

        return $workspaces
            ->map(fn (Workspace $workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'create_url' => route('projects.create', $workspace, absolute: false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{panel: string, review_count: int, assigned_count: int}
     */
    public function roleContext(User $user): array
    {
        $projectIds = $this->accessibleProjectIds($user);
        $reviewCount = Task::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', TaskStatus::Review)
            ->count();

        $assignedCount = $user->assignedTasks()->where('status', '!=', TaskStatus::Done)->count();

        $panel = match (true) {
            $user->isAdmin() => 'staff',
            $user->managedWorkspaces()->isNotEmpty() => 'workspace_lead',
            $user->managedProjects()->isNotEmpty() => 'project_lead',
            default => 'member',
        };

        return [
            'panel' => $panel,
            'review_count' => $reviewCount,
            'assigned_count' => $assignedCount,
        ];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr(end($parts), 0, 1));
    }
}
