<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class AssignTask extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Assign a visible task to an approved member of that project. Only project managers can do this.';
    }

    protected function preview(Request $request): array
    {
        if ($this->user === null) {
            return ['error' => 'Please sign in to assign tasks.'];
        }

        $workspace = null;

        if (! empty($request['workspace'])) {
            $workspace = $this->resolveWorkspace($request['workspace']);

            if ($workspace === null) {
                return ['error' => 'No workspace matched that name.'];
            }
        }

        $project = null;

        if (! empty($request['project'])) {
            $project = $this->resolveAccessibleProject($request['project'], $workspace);

            if ($project === null) {
                return ['error' => 'No visible project matched that name.'];
            }
        }

        $task = $this->resolveTask($request['task'] ?? null, $project);

        if ($task === null) {
            return ['error' => 'No visible task matched that name.'];
        }

        if (! $this->user->canManageProject($task->project)) {
            return ['error' => 'Only project managers can reassign tasks.'];
        }

        $assignee = $this->resolveProjectMember($request['assignee'] ?? null, $task->project);

        if ($assignee === null) {
            return ['error' => 'No approved project member matched that assignee.'];
        }

        if ($task->assigned_to === $assignee->id) {
            return ['error' => 'That task is already assigned to this member.'];
        }

        return [
            'summary' => "Assign task \"{$task->title}\" to {$assignee->name}",
            'changes' => [
                "Task: {$task->title}",
                'From: '.($task->assignee?->name ?? 'unassigned'),
                "To: {$assignee->name}",
            ],
            'params' => [
                'task_id' => $task->id,
                'assignee_id' => $assignee->id,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $task = $this->resolveTask((string) $params['task_id']);

        if ($task === null || ! $this->user?->canManageProject($task->project)) {
            return ['success' => false, 'message' => 'Only project managers can reassign tasks.'];
        }

        $task->loadMissing('assignee:id,name,email,locale');
        $originalAssignee = $task->assignee;
        $newAssignee = $this->resolveProjectMember((string) $params['assignee_id'], $task->project);

        if ($newAssignee === null) {
            return ['success' => false, 'message' => 'The selected assignee is not an approved project member.'];
        }

        $task->update(['assigned_to' => $newAssignee->id]);
        $task->load('assignee:id,name,email,locale');
        $task->recordAssignment($this->user, $originalAssignee, $task->assignee);

        return [
            'success' => true,
            'message' => "Assigned \"{$task->title}\" to {$newAssignee->name}.",
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()
                ->description('Task title or numeric id.')
                ->required(),
            'assignee' => $schema->string()
                ->description('Approved project member name or numeric id.')
                ->required(),
            'project' => $schema->string()
                ->description('Optional project name to disambiguate the task.'),
            'workspace' => $schema->string()
                ->description('Optional workspace name to disambiguate the project.'),
        ];
    }
}
