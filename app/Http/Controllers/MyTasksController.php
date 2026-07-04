<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Post;
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

        if (! $user->isStudent()) {
            abort(403);
        }

        $baseQuery = Task::query()
            ->assignedTo($user)
            ->with(['committee:id,club_id,name', 'committee.club:id,name'])
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

        $committeeIds = $user->committeeMemberships()
            ->where('status', 'approved')
            ->pluck('committee_id')
            ->unique()
            ->values();

        $recentUpdates = $committeeIds->isEmpty()
            ? collect()
            : Post::query()
                ->whereIn('committee_id', $committeeIds)
                ->with(['committee:id,name', 'club:id,name'])
                ->latest('published_at')
                ->limit(6)
                ->get()
                ->map(fn (Post $post): array => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'committee_name' => $post->committee?->name ?? '',
                    'club_name' => $post->club?->name ?? '',
                    'published_at' => $post->published_at?->toIso8601String(),
                    'url' => route('committees.updates.index', [$post->club_id, $post->committee_id], absolute: false),
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
            'club' => $task->committee?->club?->only(['id', 'name']),
            'committee' => $task->committee?->only(['id', 'name']),
            'detail_url' => route('committees.tasks.show', [$task->committee?->club_id, $task->committee_id, $task], absolute: false),
            'project_url' => route('committees.tasks.index', [$task->committee?->club_id, $task->committee_id], absolute: false),
            'update_url' => route('committees.tasks.update', [$task->committee?->club_id, $task->committee_id, $task], absolute: false),
            'quick_action' => $quickAction,
            'can_toggle_progress' => $task->isAssignedTo($user) && in_array($task->status, [TaskStatus::Todo, TaskStatus::InProgress], true),
        ];
    }
}
