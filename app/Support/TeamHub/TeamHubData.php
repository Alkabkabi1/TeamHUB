<?php

namespace App\Support\TeamHub;

use App\Enums\TaskActivityType;
use App\Enums\TaskStatus;
use App\Models\Club;
use App\Models\Committee;
use App\Models\Event;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeamHubData
{
    /** @var list<string> */
    private const PROJECT_COLORS = ['#7c3aed', '#16a34a', '#2563eb', '#c8924a', '#dc2626', '#0891b2'];

    /** @var list<string> */
    private const PROJECT_ICONS = ['monitor', 'mobile', 'web', 'megaphone'];

    public function accessibleCommitteeIds(User $user): Collection
    {
        if ($user->isUniversityStaff()) {
            return Committee::query()->pluck('id');
        }

        $managedClubIds = $user->managedClubs()->pluck('id');

        $membershipIds = $user->committeeMemberships()
            ->where('status', 'approved')
            ->pluck('committee_id');

        $inheritedIds = $managedClubIds->isNotEmpty()
            ? Committee::query()->whereIn('club_id', $managedClubIds)->pluck('id')
            : collect();

        return $membershipIds->merge($inheritedIds)->unique()->values();
    }

    /**
     * @return Collection<int, Club>
     */
    public function accessibleClubs(User $user): Collection
    {
        if ($user->isUniversityStaff()) {
            return Club::query()->orderBy('name')->get();
        }

        $managed = $user->managedClubs();

        $memberClubIds = $user->clubMemberships()
            ->where('status', 'approved')
            ->pluck('club_id');

        $memberClubs = $memberClubIds->isNotEmpty()
            ? Club::query()->whereIn('id', $memberClubIds)->get()
            : collect();

        return $managed->merge($memberClubs)->unique('id')->sortBy('name')->values();
    }

    /**
     * @return Builder<Committee>
     */
    public function committeesQuery(User $user, ?int $workspaceId = null): Builder
    {
        $ids = $this->accessibleCommitteeIds($user);

        return Committee::query()
            ->whereIn('id', $ids)
            ->when($workspaceId, fn (Builder $query) => $query->where('club_id', $workspaceId))
            ->with(['club:id,name,theme', 'memberships.user:id,name'])
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
        $committeeIds = $this->accessibleCommitteeIds($user);

        return Task::query()
            ->whereIn('committee_id', $committeeIds)
            ->when($workspaceId, fn (Builder $query) => $query->whereHas(
                'committee',
                fn (Builder $committee) => $committee->where('club_id', $workspaceId),
            ))
            ->with(['committee:id,club_id,name', 'committee.club:id,name', 'assignee:id,name']);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentProject(Committee $committee): array
    {
        $total = (int) $committee->tasks_count;
        $done = (int) ($committee->done_tasks_count ?? 0);
        $progress = $total > 0 ? (int) round(($done / $total) * 100) : 0;
        $color = self::PROJECT_COLORS[$committee->id % count(self::PROJECT_COLORS)];
        $icon = self::PROJECT_ICONS[$committee->id % count(self::PROJECT_ICONS)];

        $members = $committee->memberships
            ->take(4)
            ->map(fn ($membership) => $this->initials((string) $membership->user?->name))
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $committee->id,
            'club_id' => $committee->club_id,
            'title' => $committee->name,
            'description' => $committee->description ?? '',
            'progress' => $progress,
            'tasksCount' => $total,
            'membersCount' => (int) ($committee->members_count ?? 0),
            'color' => $committee->theme ?: ($committee->club?->theme ?: $color),
            'icon' => $icon,
            'members' => $members,
            'url' => route('committees.tasks.index', [$committee->club_id, $committee], absolute: false),
            'manage_url' => route('committees.manage', [$committee->club_id, $committee], absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentTask(Task $task): array
    {
        $assigneeName = $task->assignee?->name ?? __('hub.unassigned');

        return [
            'id' => $task->id,
            'title' => $task->title,
            'project' => $task->committee?->name ?? '',
            'priority' => $task->priority->value,
            'dueDate' => $task->due_at?->toDateString() ?? '',
            'dueLabel' => $task->due_at
                ? $task->due_at->locale(app()->getLocale())->translatedFormat('j M')
                : '—',
            'status' => $task->status->value,
            'assignee' => [
                'name' => $assigneeName,
                'initials' => $this->initials($assigneeName),
            ],
            'url' => route('committees.tasks.show', [
                $task->committee?->club_id,
                $task->committee_id,
                $task,
            ], absolute: false),
            'committee_id' => $task->committee_id,
            'club_id' => $task->committee?->club_id,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function kpis(User $user): array
    {
        $committeeIds = $this->accessibleCommitteeIds($user);
        $tasks = Task::query()->whereIn('committee_id', $committeeIds);

        $totalProjects = Committee::query()->whereIn('id', $committeeIds)->count();
        $overdue = (clone $tasks)->overdue()->count();
        $inProgress = (clone $tasks)->where('status', TaskStatus::InProgress)->count();
        $done = (clone $tasks)->where('status', TaskStatus::Done)->count();
        $review = (clone $tasks)->where('status', TaskStatus::Review)->count();

        return [
            [
                'id' => 'projects',
                'label' => __('hub.kpis.projects'),
                'value' => $totalProjects,
                'trend' => '',
                'trendUp' => true,
                'icon' => 'projects',
            ],
            [
                'id' => 'overdue',
                'label' => __('hub.kpis.overdue'),
                'value' => $overdue,
                'trend' => '',
                'trendUp' => false,
                'icon' => 'overdue',
            ],
            [
                'id' => 'progress',
                'label' => __('hub.kpis.in_progress'),
                'value' => $inProgress,
                'trend' => '',
                'trendUp' => true,
                'icon' => 'progress',
            ],
            [
                'id' => 'done',
                'label' => __('hub.kpis.done'),
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
        $committeeIds = $this->accessibleCommitteeIds($user);

        return TaskActivity::query()
            ->whereHas('task', fn (Builder $query) => $query->whereIn('committee_id', $committeeIds))
            ->with(['user:id,name', 'task:id,title,committee_id', 'task.committee:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (TaskActivity $activity): array {
                $userName = $activity->user?->name ?? __('hub.system');
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
        $committeeIds = $this->accessibleCommitteeIds($user);
        $markers = [];

        Task::query()
            ->whereIn('committee_id', $committeeIds)
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

        $clubIds = $this->accessibleClubs($user)->pluck('id');

        Event::query()
            ->where(function (Builder $query) use ($clubIds, $committeeIds): void {
                $query->whereIn('club_id', $clubIds)
                    ->orWhereIn('committee_id', $committeeIds);
            })
            ->where('starts_at', '>=', now()->subMonths(1))
            ->select(['id', 'title', 'starts_at'])
            ->get()
            ->each(function (Event $event) use (&$markers): void {
                $markers[] = [
                    'date' => $event->starts_at->toDateString(),
                    'title' => $event->title,
                    'type' => 'event',
                ];
            });

        return $markers;
    }

    /**
     * @return list<array{id: int, name: string, letter: string, color: string, url: string}>
     */
    public function workspaces(User $user): array
    {
        return $this->accessibleClubs($user)
            ->map(function (Club $club, int $index): array {
                $letter = mb_substr($club->name, 0, 1);

                return [
                    'id' => $club->id,
                    'name' => $club->name,
                    'letter' => $letter,
                    'color' => self::PROJECT_COLORS[$club->id % count(self::PROJECT_COLORS)],
                    'url' => route('hub.projects', ['workspace' => $club->id], absolute: false),
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
        $clubs = $user->isUniversityStaff()
            ? Club::query()->orderBy('name')->get()
            : $user->managedClubs();

        return $clubs
            ->map(fn (Club $club): array => [
                'id' => $club->id,
                'name' => $club->name,
                'create_url' => route('committees.create', $club, absolute: false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{panel: string, review_count: int, assigned_count: int}
     */
    public function roleContext(User $user): array
    {
        $committeeIds = $this->accessibleCommitteeIds($user);
        $reviewCount = Task::query()
            ->whereIn('committee_id', $committeeIds)
            ->where('status', TaskStatus::Review)
            ->count();

        $assignedCount = $user->assignedTasks()->where('status', '!=', TaskStatus::Done)->count();

        $panel = match (true) {
            $user->isUniversityStaff() => 'staff',
            $user->managedClubs()->isNotEmpty() => 'club_lead',
            $user->managedCommittees()->isNotEmpty() => 'committee_lead',
            default => 'student',
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
