<?php

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class FindTasks extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Search visible tasks by title, project, assignee, status, or priority. Use this when the '
            .'user asks to find a task before summarizing, assigning, or editing it.';
    }

    public function handle(Request $request): Stringable|string
    {
        if ($this->user === null) {
            return $this->json(['error' => 'Please sign in to search tasks.']);
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

        $search = trim((string) ($request['search'] ?? ''));
        $status = (string) ($request['status'] ?? '');
        $priority = (string) ($request['priority'] ?? '');
        $assignee = trim((string) ($request['assignee'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 15), 1), 30);

        $tasks = $this->visibleTaskQuery()
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->id))
            ->when(
                $workspace !== null && $project === null,
                fn ($q) => $q->whereHas('project', fn ($projectQuery) => $projectQuery->where('workspace_id', $workspace->id)),
            )
            ->when(
                $search !== '',
                fn ($q) => $q->where(function ($taskQuery) use ($search): void {
                    $taskQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }),
            )
            ->when(
                $assignee !== '',
                fn ($q) => $q->whereHas('assignee', fn ($assigneeQuery) => $assigneeQuery->where('name', 'like', "%{$assignee}%")),
            )
            ->when($status !== '' && in_array($status, TaskStatus::values(), true), fn ($q) => $q->where('status', $status))
            ->when($priority !== '' && in_array($priority, TaskPriority::values(), true), fn ($q) => $q->where('priority', $priority))
            ->orderByRaw("case status when 'review' then 0 when 'in_progress' then 1 when 'todo' then 2 else 3 end")
            ->orderBy('due_at')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return $this->json([
            'search' => $search,
            'workspace' => $workspace?->name,
            'project' => $project?->name,
            'count' => $tasks->count(),
            'tasks' => $tasks->map(fn (Task $task) => $this->presentTask($task))->values()->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Optional keyword to match against task title or description.'),
            'workspace' => $schema->string()
                ->description('Optional workspace name to narrow the search.'),
            'project' => $schema->string()
                ->description('Optional project name to narrow the search.'),
            'assignee' => $schema->string()
                ->description('Optional assignee name to filter by.'),
            'status' => $schema->string()
                ->enum(TaskStatus::values())
                ->description('Optional task status filter.'),
            'priority' => $schema->string()
                ->enum(TaskPriority::values())
                ->description('Optional task priority filter.'),
            'limit' => $schema->integer()->min(1)->max(30)
                ->description('Maximum number of tasks to return (default 15).'),
        ];
    }
}
