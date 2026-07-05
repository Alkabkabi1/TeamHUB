<?php

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListMyTasks extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List the current user\'s tasks by urgency or project. Use this for questions like '
            .'"what tasks are overdue?", "what is due today?", or "show my tasks in Project X".';
    }

    public function handle(Request $request): Stringable|string
    {
        if ($this->user === null) {
            return $this->json(['error' => 'Please sign in to view your tasks.']);
        }

        $workspace = null;

        if (! empty($request['workspace'])) {
            $workspace = $this->resolveWorkspace($request['workspace']);

            if ($workspace === null) {
                return $this->json(['error' => 'No workspace matched that name.']);
            }
        }

        $project = null;

        if (! empty($request['project'])) {
            $project = $this->resolveAccessibleProject($request['project'], $workspace);

            if ($project === null) {
                return $this->json(['error' => 'No visible project matched that name.']);
            }
        }

        $bucket = (string) ($request['bucket'] ?? 'open');
        $limit = min(max((int) ($request['limit'] ?? 12), 1), 30);
        $status = (string) ($request['status'] ?? '');
        $priority = (string) ($request['priority'] ?? '');

        $query = Task::query()
            ->assignedTo($this->user)
            ->with(['project:id,workspace_id,name', 'project.workspace:id,name', 'assignee:id,name', 'creator:id,name'])
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->id))
            ->when($status !== '' && in_array($status, TaskStatus::values(), true), fn ($q) => $q->where('status', $status))
            ->when($priority !== '' && in_array($priority, TaskPriority::values(), true), fn ($q) => $q->where('priority', $priority))
            ->orderBy('due_at')
            ->orderByDesc('updated_at');

        $tasks = match ($bucket) {
            'overdue' => (clone $query)->overdue()->limit($limit)->get(),
            'due_today' => (clone $query)->dueToday()->limit($limit)->get(),
            'upcoming' => (clone $query)->upcoming()->limit($limit)->get(),
            'no_due_date' => (clone $query)->withoutDueDate()->limit($limit)->get(),
            'done' => (clone $query)->where('status', TaskStatus::Done->value)->limit($limit)->get(),
            default => (clone $query)->incomplete()->limit($limit)->get(),
        };

        $summaryBase = Task::query()->assignedTo($this->user);

        $summary = [
            'openCount' => (clone $summaryBase)->incomplete()->count(),
            'overdueCount' => (clone $summaryBase)->overdue()->count(),
            'dueTodayCount' => (clone $summaryBase)->dueToday()->count(),
            'upcomingCount' => (clone $summaryBase)->upcoming()->count(),
            'noDueDateCount' => (clone $summaryBase)->withoutDueDate()->count(),
            'doneCount' => (clone $summaryBase)->where('status', TaskStatus::Done->value)->count(),
        ];

        $byProject = $tasks
            ->groupBy('project_id')
            ->map(fn ($projectTasks) => [
                'project' => $projectTasks->first()?->project?->name ?? '',
                'workspace' => $projectTasks->first()?->project?->workspace?->name ?? '',
                'count' => $projectTasks->count(),
            ])
            ->values()
            ->all();

        return $this->json([
            'bucket' => $bucket,
            'workspace' => $workspace?->name,
            'project' => $project?->name,
            'summary' => $summary,
            'byProject' => $byProject,
            'tasks' => $tasks->map(fn (Task $task) => $this->presentTask($task))->values()->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'bucket' => $schema->string()
                ->enum(['open', 'overdue', 'due_today', 'upcoming', 'no_due_date', 'done'])
                ->description('Which slice of the current user\'s tasks to return.'),
            'workspace' => $schema->string()
                ->description('Optional workspace name to help disambiguate a project.'),
            'project' => $schema->string()
                ->description('Optional project name to limit the task list to one project.'),
            'status' => $schema->string()
                ->enum(TaskStatus::values())
                ->description('Optional status filter.'),
            'priority' => $schema->string()
                ->enum(TaskPriority::values())
                ->description('Optional priority filter.'),
            'limit' => $schema->integer()->min(1)->max(30)
                ->description('Maximum number of tasks to return (default 12).'),
        ];
    }
}
