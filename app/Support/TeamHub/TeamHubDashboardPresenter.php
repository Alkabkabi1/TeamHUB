<?php

namespace App\Support\TeamHub;

use App\Enums\CommitteeRole;
use App\Enums\TaskStatus;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Task;
use App\Models\User;
use App\Support\DemoRoles;
use App\Support\DemoWorkspace;

class TeamHubDashboardPresenter
{
    public function __construct(private TeamHubData $hub) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, ?string $persona): array
    {
        return match ($persona) {
            'admin' => ['panel' => $this->adminPanel($user)],
            'project_leader' => ['panel' => $this->leaderPanel($user)],
            'staff' => ['panel' => $this->staffPanel($user)],
            default => ['panel' => $this->legacyPanel($user)],
        };
    }

    public function greeting(User $user, ?string $persona): string
    {
        if ($persona !== null) {
            return __('hub.persona_greeting', [
                'role' => __("auth.demo_roles.{$persona}"),
            ]);
        }

        return __('hub.greeting', ['name' => $user->name]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminPanel(User $user): array
    {
        $leaders = User::query()
            ->whereIn('email', collect(DemoRoles::accounts())->where('role', 'project_leader')->pluck('email'))
            ->get(['id', 'name', 'email']);

        $projects = Committee::query()
            ->with(['club:id,name', 'memberships.user:id,name,email', 'memberships.roles'])
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Done),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Committee $committee): array {
                $leader = $this->committeeLeader($committee);

                return [
                    'id' => $committee->id,
                    'club_id' => $committee->club_id,
                    'title' => $committee->name,
                    'workspace' => $committee->club?->name ?? '',
                    'progress' => $this->progressPercent($committee),
                    'tasks_count' => (int) $committee->tasks_count,
                    'leader' => $leader ? [
                        'id' => $leader->id,
                        'name' => $leader->name,
                        'email' => $leader->email,
                    ] : null,
                    'url' => route('committees.tasks.index', [$committee->club_id, $committee], absolute: false),
                ];
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
            'workspaces' => DemoWorkspace::options(),
            'stats' => [
                'projects' => count($projects),
                'leaders' => $leaders->count(),
                'open_tasks' => Task::query()->where('status', '!=', TaskStatus::Done)->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leaderPanel(User $user): array
    {
        $committee = $user->managedCommittees()->first()
            ?? $this->hub->committeesQuery($user)->first();

        if ($committee === null) {
            return [
                'type' => 'project_leader',
                'project' => null,
                'team' => [],
                'review_queue' => [],
                'members' => [],
            ];
        }

        $committee->load(['club:id,name', 'memberships.user:id,name', 'memberships.roles']);

        $tasks = Task::query()
            ->forCommittee($committee)
            ->with('assignee:id,name')
            ->get();

        $team = $committee->memberships
            ->filter(fn (CommitteeMembership $membership) => $membership->user_id !== $user->id)
            ->map(function (CommitteeMembership $membership) use ($tasks): array {
                $memberTasks = $tasks->where('assigned_to', $membership->user_id);
                $done = $memberTasks->where('status', TaskStatus::Done)->count();

                return [
                    'id' => $membership->user_id,
                    'name' => $membership->user?->name ?? __('hub.unassigned'),
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
            ->map(fn (Task $task): array => $this->presentStaffTask($task))
            ->values()
            ->all();

        $members = $committee->memberships
            ->map(fn (CommitteeMembership $membership): array => [
                'id' => $membership->user_id,
                'name' => $membership->user?->name ?? '',
            ])
            ->filter(fn (array $member) => $member['id'] !== $user->id)
            ->values()
            ->all();

        return [
            'type' => 'project_leader',
            'project' => [
                'id' => $committee->id,
                'club_id' => $committee->club_id,
                'title' => $committee->name,
                'workspace' => $committee->club?->name ?? '',
                'progress' => $this->progressPercent($committee),
                'tasks_count' => $tasks->count(),
                'url' => route('committees.tasks.index', [$committee->club_id, $committee], absolute: false),
                'manage_url' => route('committees.manage', [$committee->club_id, $committee], absolute: false),
            ],
            'team' => $team,
            'review_queue' => $reviewQueue,
            'members' => $members,
            'open_tasks' => $tasks->where('status', '!=', TaskStatus::Done)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staffPanel(User $user): array
    {
        $tasks = Task::query()
            ->assignedTo($user)
            ->with(['committee:id,club_id,name', 'committee.club:id,name'])
            ->orderBy('due_at')
            ->get()
            ->map(fn (Task $task): array => $this->presentStaffTask($task))
            ->values()
            ->all();

        return [
            'type' => 'staff',
            'tasks' => $tasks,
            'stats' => [
                'open' => collect($tasks)->where('status', '!=', 'done')->count(),
                'due_today' => collect($tasks)->filter(fn (array $task) => $task['due_today'])->count(),
                'in_review' => collect($tasks)->where('status', 'review')->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyPanel(User $user): array
    {
        return [
            'type' => 'legacy',
            'roleContext' => $this->hub->roleContext($user),
            'kpis' => $this->hub->kpis($user),
            'projects' => $this->hub->committeesQuery($user)->limit(4)->get()
                ->map(fn (Committee $committee) => $this->hub->presentProject($committee))
                ->all(),
            'tasks' => $this->hub->tasksQuery($user)->dueToday()->limit(8)->get()
                ->map(fn (Task $task) => $this->hub->presentTask($task))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentStaffTask(Task $task): array
    {
        $hasDeliverable = filled($task->deliverable_url)
            || filled($task->deliverable_notes)
            || $task->getFirstMedia(Task::DELIVERABLE_COLLECTION) !== null;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'status_label' => __($task->status->label()),
            'priority' => $task->priority->value,
            'due_at' => $task->due_at?->toIso8601String(),
            'due_label' => $task->due_at
                ? $task->due_at->locale(app()->getLocale())->translatedFormat('j M Y')
                : '—',
            'due_today' => $task->due_at?->isToday() ?? false,
            'project' => $task->committee?->name ?? '',
            'club_id' => $task->committee?->club_id,
            'committee_id' => $task->committee_id,
            'has_deliverable' => $hasDeliverable,
            'deliverable_url' => $task->deliverable_url,
            'deliverable_notes' => $task->deliverable_notes,
            'can_submit' => in_array($task->status, [TaskStatus::Todo, TaskStatus::InProgress], true),
            'detail_url' => route('committees.tasks.show', [
                $task->committee?->club_id,
                $task->committee_id,
                $task,
            ], absolute: false),
            'submit_url' => route('hub.staff.deliverable', $task, absolute: false),
        ];
    }

    private function committeeLeader(Committee $committee): ?User
    {
        /** @var CommitteeMembership|null $membership */
        $membership = $committee->memberships
            ->first(fn (CommitteeMembership $membership) => $membership->roles
                ->pluck('role')
                ->intersect(CommitteeRole::managerRoleValues())
                ->isNotEmpty());

        return $membership?->user;
    }

    private function progressPercent(Committee $committee): int
    {
        $total = (int) ($committee->tasks_count ?? 0);
        $done = (int) ($committee->done_tasks_count ?? 0);

        return $total > 0 ? (int) round(($done / $total) * 100) : 0;
    }
}
