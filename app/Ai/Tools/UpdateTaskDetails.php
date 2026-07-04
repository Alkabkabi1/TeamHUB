<?php

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTaskDetails extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Update editable task details such as title, description, priority, and due date. Only project managers can do this.';
    }

    protected function preview(Request $request): array
    {
        if ($this->user === null) {
            return ['error' => 'Please sign in to edit tasks.'];
        }

        $workspace = null;

        if (! empty($request['workspace'])) {
            $workspace = $this->resolveClub($request['workspace']);

            if ($workspace === null) {
                return ['error' => 'No workspace matched that name.'];
            }
        }

        $project = null;

        if (! empty($request['project'])) {
            $project = $this->resolveAccessibleCommittee($request['project'], $workspace);

            if ($project === null) {
                return ['error' => 'No visible project matched that name.'];
            }
        }

        $task = $this->resolveTask($request['task'] ?? null, $project);

        if ($task === null) {
            return ['error' => 'No visible task matched that name.'];
        }

        if (! $this->user->canManageCommittee($task->committee)) {
            return ['error' => 'Only project managers can edit these task details.'];
        }

        $params = ['task_id' => $task->id];
        $changes = [];

        if (array_key_exists('title', $request->all())) {
            $title = trim((string) $request['title']);

            if ($title === '') {
                return ['error' => 'Task title cannot be blank.'];
            }

            if ($title !== $task->title) {
                $params['title'] = $title;
                $changes[] = "Title: \"{$task->title}\" → \"{$title}\"";
            }
        }

        if (array_key_exists('description', $request->all()) && ($request['description'] ?? null) !== $task->description) {
            $params['description'] = $request['description'];
            $changes[] = 'Description: updated';
        }

        if (array_key_exists('priority', $request->all())) {
            $priority = (string) $request['priority'];

            if (! in_array($priority, TaskPriority::values(), true)) {
                return ['error' => 'Task priority is invalid.'];
            }

            if ($priority !== $task->priority->value) {
                $params['priority'] = $priority;
                $changes[] = "Priority: {$task->priority->value} → {$priority}";
            }
        }

        $clearDueDate = filter_var($request['clear_due_date'] ?? false, FILTER_VALIDATE_BOOL);

        if ($clearDueDate && $task->due_at !== null) {
            $params['clear_due_date'] = true;
            $changes[] = 'Due date: cleared';
        }

        if (array_key_exists('due_at', $request->all()) && ! $clearDueDate) {
            try {
                $dueAt = Carbon::parse((string) $request['due_at']);
            } catch (\Throwable) {
                return ['error' => 'Due date is invalid.'];
            }

            if ($task->due_at?->toIso8601String() !== $dueAt->toIso8601String()) {
                $params['due_at'] = $dueAt->toIso8601String();
                $changes[] = 'Due date: '.($task->due_at?->translatedFormat('d F Y H:i') ?? 'not set')
                    .' → '.$dueAt->translatedFormat('d F Y H:i');
            }
        }

        if ($changes === []) {
            return ['error' => 'No task detail changes were detected.'];
        }

        return [
            'summary' => "Update details for task \"{$task->title}\"",
            'changes' => $changes,
            'params' => $params,
        ];
    }

    public function execute(array $params): array
    {
        $task = $this->resolveTask((string) $params['task_id']);

        if ($task === null || ! $this->user?->canManageCommittee($task->committee)) {
            return ['success' => false, 'message' => 'Only project managers can edit these task details.'];
        }

        $updates = [];

        foreach (['title', 'description', 'priority'] as $field) {
            if (array_key_exists($field, $params)) {
                $updates[$field] = $params[$field];
            }
        }

        if (! empty($params['clear_due_date'])) {
            $updates['due_at'] = null;
        } elseif (array_key_exists('due_at', $params)) {
            $updates['due_at'] = Carbon::parse($params['due_at']);
        }

        $task->update($updates);

        return [
            'success' => true,
            'message' => "Updated details for \"{$task->title}\".",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()
                ->description('Task title or numeric id.')
                ->required(),
            'project' => $schema->string()
                ->description('Optional project name to disambiguate the task.'),
            'workspace' => $schema->string()
                ->description('Optional workspace name to disambiguate the project.'),
            'title' => $schema->string()
                ->description('New task title.'),
            'description' => $schema->string()
                ->description('New task description.'),
            'priority' => $schema->string()
                ->enum(TaskPriority::values())
                ->description('New task priority.'),
            'due_at' => $schema->string()
                ->description('New due date/time. Prefer ISO 8601 when possible.'),
            'clear_due_date' => $schema->string()
                ->description('Set to true to clear the due date instead of updating it.'),
        ];
    }
}
