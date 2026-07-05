<?php

namespace App\Actions\Dashboard;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class CreateProjectTaskAction
{
    /**
     * @param  array{title: string, assigned_to?: int|null, due_at?: string|null, priority?: string|null, description?: string|null}  $data
     */
    public function execute(User $actor, Project $project, array $data): Task
    {
        $task = Task::query()->create([
            'project_id' => $project->id,
            'created_by' => $actor->id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => TaskStatus::Todo,
            'priority' => $data['priority'] ?? TaskPriority::Medium->value,
            'due_at' => $data['due_at'] ?? null,
        ]);

        $task->recordCreated($actor);

        if ($task->assigned_to !== null) {
            $task->loadMissing('assignee:id,name,email,locale');
            $task->recordAssignment($actor, null, $task->assignee);
        }

        return $task;
    }
}
