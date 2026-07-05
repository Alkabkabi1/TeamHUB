<?php

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTask extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Create a task in a project you manage. Use this for requests like "create a task for Ahmed due Friday".';
    }

    protected function preview(Request $request): array
    {
        if ($this->user === null) {
            return ['error' => 'Please sign in to create tasks.'];
        }

        $workspace = null;

        if (! empty($request['workspace'])) {
            $workspace = $this->resolveWorkspace($request['workspace']);

            if ($workspace === null) {
                return ['error' => 'No workspace matched that name.'];
            }
        }

        $project = $this->resolveAccessibleProject($request['project'] ?? null, $workspace);

        if ($project === null) {
            return ['error' => 'No visible project matched that name.'];
        }

        if (! $this->user->can('create', [Task::class, $project])) {
            return ['error' => 'You are not allowed to create tasks in this project.'];
        }

        $title = trim((string) ($request['title'] ?? ''));

        if ($title === '') {
            return ['error' => 'Task title is required.'];
        }

        $description = $request['description'] ?? null;
        $priority = (string) ($request['priority'] ?? TaskPriority::Medium->value);
        $status = (string) ($request['status'] ?? TaskStatus::Todo->value);

        if (! in_array($priority, TaskPriority::values(), true)) {
            return ['error' => 'Task priority is invalid.'];
        }

        if (! in_array($status, [TaskStatus::Todo->value, TaskStatus::InProgress->value], true)) {
            return ['error' => 'Tasks can only be created as todo or in progress.'];
        }

        $assignee = null;

        if (! empty($request['assignee'])) {
            $assignee = $this->resolveProjectMember($request['assignee'], $project);

            if ($assignee === null) {
                return ['error' => 'No approved project member matched that assignee.'];
            }
        }

        $dueAt = null;

        if (! empty($request['due_at'])) {
            try {
                $dueAt = Carbon::parse((string) $request['due_at']);
            } catch (\Throwable) {
                return ['error' => 'Due date is invalid.'];
            }
        }

        $changes = [
            "Create task \"{$title}\"",
            "Project: {$project->name}",
            'Status: '.$status,
            'Priority: '.$priority,
        ];

        if ($assignee !== null) {
            $changes[] = "Assignee: {$assignee->name}";
        }

        if ($dueAt !== null) {
            $changes[] = 'Due: '.$dueAt->translatedFormat('d F Y H:i');
        }

        if (filled($description)) {
            $changes[] = 'Description: included';
        }

        return [
            'summary' => "Create task \"{$title}\" in {$project->name}",
            'changes' => $changes,
            'params' => [
                'project_id' => $project->id,
                'created_by' => $this->user->id,
                'title' => $title,
                'description' => $description,
                'assigned_to' => $assignee?->id,
                'priority' => $priority,
                'status' => $status,
                'due_at' => $dueAt?->toIso8601String(),
            ],
        ];
    }

    public function execute(array $params): array
    {
        $project = $this->resolveAccessibleProject((string) $params['project_id']);

        if ($project === null || ! $this->user?->can('create', [Task::class, $project])) {
            return ['success' => false, 'message' => 'You are not allowed to create tasks in this project.'];
        }

        $task = Task::create([
            'project_id' => $project->id,
            'created_by' => $this->user->id,
            'title' => $params['title'],
            'description' => $params['description'],
            'assigned_to' => $params['assigned_to'],
            'priority' => $params['priority'],
            'status' => $params['status'],
            'due_at' => isset($params['due_at']) ? Carbon::parse($params['due_at']) : null,
        ]);

        $task->recordCreated($this->user);
        $task->loadMissing('assignee:id,name,email,locale');

        if ($task->assigned_to !== null) {
            $task->recordAssignment($this->user, null, $task->assignee);
        }

        return [
            'success' => true,
            'message' => "Created task \"{$task->title}\".",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()
                ->description('Project name or numeric id.')
                ->required(),
            'workspace' => $schema->string()
                ->description('Optional workspace name to disambiguate the project.'),
            'title' => $schema->string()
                ->description('Task title.')
                ->required(),
            'description' => $schema->string()
                ->description('Optional task description.'),
            'assignee' => $schema->string()
                ->description('Optional assignee name or numeric id.'),
            'priority' => $schema->string()
                ->enum(TaskPriority::values())
                ->description('Task priority (default: medium).'),
            'status' => $schema->string()
                ->enum([TaskStatus::Todo->value, TaskStatus::InProgress->value])
                ->description('Initial status (todo or in_progress).'),
            'due_at' => $schema->string()
                ->description('Optional due date/time. Prefer ISO 8601 when possible.'),
        ];
    }
}
