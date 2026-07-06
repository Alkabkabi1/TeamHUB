<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ProjectUpdate;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyTasksController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isMember()) {
            abort(403);
        }

        $baseQuery = Task::query()
            ->assignedTo($user)
            ->with(['project:id,workspace_id,name', 'project.workspace:id,name'])
            ->orderBy('due_at')
            ->orderByDesc('updated_at');

        $overdue = (clone $baseQuery)
            ->overdue()
            ->get()
            ->map(fn (Task $task): array => $this->presentTask($task, $user))
            ->values();

        $dueToday = (clone $baseQuery)
            ->dueToday()
            ->get()
            ->map(fn (Task $task): array => $this->presentTask($task, $user))
            ->values();

        $upcoming = (clone $baseQuery)
            ->upcoming()
            ->get()
            ->map(fn (Task $task): array => $this->presentTask($task, $user))
            ->values();

        $noDueDate = (clone $baseQuery)
            ->withoutDueDate()
            ->get()
            ->map(fn (Task $task): array => $this->presentTask($task, $user))
            ->values();

        $projectIds = $user->projectMemberships()
            ->where('status', 'approved')
            ->pluck('project_id')
            ->unique()
            ->values();

        $recentUpdates = $projectIds->isEmpty()
            ? collect()
            : ProjectUpdate::query()
                ->whereIn('project_id', $projectIds)
                ->with(['project:id,name', 'workspace:id,name'])
                ->latest('published_at')
                ->limit(6)
                ->get()
                ->map(fn (ProjectUpdate $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'project_name' => $post->project?->name ?? '',
                    'workspace_name' => $post->workspace?->name ?? '',
                    'published_at' => $post->published_at?->toIso8601String(),
                    'url' => route('projects.updates.index', [$post->workspace_id, $post->project_id], absolute: false),
                ])
                ->values();

        return Inertia::render('MyTasks', [
            'summary' => [
                'overdue_count' => $overdue->count(),
                'due_today_count' => $dueToday->count(),
                'upcoming_count' => $upcoming->count(),
                'no_due_date_count' => $noDueDate->count(),
                'open_count' => $overdue->count() + $dueToday->count() + $upcoming->count() + $noDueDate->count(),
            ],
            'overdueTasks' => $overdue,
            'dueTodayTasks' => $dueToday,
            'upcomingTasks' => $upcoming,
            'noDueDateTasks' => $noDueDate,
            'recentUpdates' => $recentUpdates,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentTask(Task $task, User $user): array
    {
        $quickAction = null;

        if ($task->status === TaskStatus::Todo) {
            $quickAction = [
                'label' => __('tasks.start_work'),
                'value' => TaskStatus::InProgress->value,
            ];
        } elseif ($task->status === TaskStatus::InProgress) {
            $quickAction = [
                'label' => __('tasks.mark_todo'),
                'value' => TaskStatus::Todo->value,
            ];
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'status_label' => __($task->status->label()),
            'priority' => $task->priority->value,
            'priority_label' => __($task->priority->label()),
            'due_at' => $task->due_at?->toIso8601String(),
            'has_deliverable' => $task->getFirstMedia(Task::DELIVERABLE_COLLECTION) !== null
                || filled($task->deliverable_url)
                || filled($task->deliverable_notes),
            'workspace' => $task->project?->workspace?->only(['id', 'name']),
            'project' => $task->project?->only(['id', 'name']),
            'detail_url' => route('projects.tasks.show', [$task->project?->workspace_id, $task->project_id, $task], absolute: false),
            'project_url' => route('projects.tasks.index', [$task->project?->workspace_id, $task->project_id], absolute: false),
            'update_url' => route('projects.tasks.update', [$task->project?->workspace_id, $task->project_id, $task], absolute: false),
            'quick_action' => $quickAction,
            'can_toggle_progress' => $task->isAssignedTo($user) && in_array($task->status, [TaskStatus::Todo, TaskStatus::InProgress], true),
        ];
    }
}
