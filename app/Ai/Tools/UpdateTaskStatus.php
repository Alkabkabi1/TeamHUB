<?php

namespace App\Ai\Tools;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTaskStatus extends WriteTool
{
    public function description(): Stringable|string
    {
        return 'Update a task status using the existing TeamHUB workflow. This supports standard progress changes, '
            .'submitting work for review, requesting changes, and approving reviewed tasks.';
    }

    protected function preview(Request $request): array
    {
        if ($this->user === null) {
            return ['error' => 'Please sign in to update task status.'];
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

        $targetStatus = (string) ($request['status'] ?? '');

        if (! in_array($targetStatus, TaskStatus::values(), true)) {
            return ['error' => 'Task status is invalid.'];
        }

        if ($targetStatus === $task->status->value) {
            return ['error' => 'The task is already in that status.'];
        }

        $changes = [
            "Task: {$task->title}",
            "Status: {$task->status->value} → {$targetStatus}",
        ];

        if ($targetStatus === TaskStatus::Review->value) {
            if (! $this->user->can('submitDeliverable', $task)) {
                return ['error' => 'You are not allowed to submit this task for review.'];
            }

            $deliverableUrl = $request['deliverable_url'] ?? null;
            $deliverableNotes = $request['deliverable_notes'] ?? null;

            if (blank($deliverableUrl) && blank($deliverableNotes)) {
                return ['error' => 'Submitting for review requires a deliverable link or notes.'];
            }

            if (filled($deliverableUrl)) {
                $changes[] = 'Deliverable link: included';
            }

            if (filled($deliverableNotes)) {
                $changes[] = 'Deliverable notes: included';
            }

            return [
                'summary' => "Submit task \"{$task->title}\" for review",
                'changes' => $changes,
                'params' => [
                    'task_id' => $task->id,
                    'mode' => 'submit_for_review',
                    'deliverable_url' => $deliverableUrl,
                    'deliverable_notes' => $deliverableNotes,
                ],
            ];
        }

        if ($targetStatus === TaskStatus::Done->value) {
            if (! $this->user->can('approveDeliverable', $task)) {
                return ['error' => 'Only project managers can approve a task into done.'];
            }

            if ($task->status !== TaskStatus::Review) {
                return ['error' => 'A task must be in review before it can be approved as done.'];
            }

            if (filled($request['review_notes'] ?? null)) {
                $changes[] = 'Review notes: included';
            }

            return [
                'summary' => "Approve task \"{$task->title}\"",
                'changes' => $changes,
                'params' => [
                    'task_id' => $task->id,
                    'mode' => 'approve',
                    'review_notes' => $request['review_notes'] ?? null,
                ],
            ];
        }

        if (
            $task->status === TaskStatus::Review
            && $targetStatus === TaskStatus::InProgress->value
            && $this->user->can('requestChanges', $task)
        ) {
            if (filled($request['review_notes'] ?? null)) {
                $changes[] = 'Review notes: included';
            }

            return [
                'summary' => "Request changes on task \"{$task->title}\"",
                'changes' => $changes,
                'params' => [
                    'task_id' => $task->id,
                    'mode' => 'request_changes',
                    'review_notes' => $request['review_notes'] ?? null,
                ],
            ];
        }

        if (! $this->user->can('update', $task)) {
            return ['error' => 'You are not allowed to update this task status.'];
        }

        if (
            ! $this->user->canManageProject($task->project)
            && in_array($task->status, [TaskStatus::Review, TaskStatus::Done], true)
        ) {
            return ['error' => 'Assignees can only change tasks that are still in todo or in progress.'];
        }

        return [
            'summary' => "Update status for task \"{$task->title}\"",
            'changes' => $changes,
            'params' => [
                'task_id' => $task->id,
                'mode' => 'direct',
                'status' => $targetStatus,
            ],
        ];
    }

    public function execute(array $params): array
    {
        $task = $this->resolveTask((string) $params['task_id']);

        if ($task === null) {
            return ['success' => false, 'message' => 'The task could not be found.'];
        }

        return match ($params['mode']) {
            'submit_for_review' => $this->submitForReview($task, $params),
            'approve' => $this->approveTask($task, $params),
            'request_changes' => $this->requestChanges($task, $params),
            default => $this->updateDirectly($task, $params),
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()
                ->description('Task title or numeric id.')
                ->required(),
            'status' => $schema->string()
                ->enum(TaskStatus::values())
                ->description('Target task status.')
                ->required(),
            'project' => $schema->string()
                ->description('Optional project name to disambiguate the task.'),
            'workspace' => $schema->string()
                ->description('Optional workspace name to disambiguate the project.'),
            'deliverable_url' => $schema->string()
                ->description('Deliverable link when moving a task to review.'),
            'deliverable_notes' => $schema->string()
                ->description('Deliverable notes when moving a task to review.'),
            'review_notes' => $schema->string()
                ->description('Optional approval or changes-request notes.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string}
     */
    private function submitForReview(Task $task, array $params): array
    {
        if (! $this->user?->can('submitDeliverable', $task)) {
            return ['success' => false, 'message' => 'You are not allowed to submit this task for review.'];
        }

        $task->submitDeliverable(
            $this->user,
            $params['deliverable_url'] ?? null,
            $params['deliverable_notes'] ?? null,
            false,
        );

        return [
            'success' => true,
            'message' => "Submitted \"{$task->title}\" for review.",
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string}
     */
    private function approveTask(Task $task, array $params): array
    {
        if (! $this->user?->can('approveDeliverable', $task)) {
            return ['success' => false, 'message' => 'Only project managers can approve this task.'];
        }

        if ($task->status !== TaskStatus::Review) {
            return ['success' => false, 'message' => 'Only tasks in review can be approved as done.'];
        }

        $task->approve($this->user, $params['review_notes'] ?? null);

        return [
            'success' => true,
            'message' => "Approved \"{$task->title}\" as done.",
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string}
     */
    private function requestChanges(Task $task, array $params): array
    {
        if (! $this->user?->can('requestChanges', $task)) {
            return ['success' => false, 'message' => 'Only project managers can request changes on this task.'];
        }

        $task->requestChanges($this->user, $params['review_notes'] ?? null);

        return [
            'success' => true,
            'message' => "Requested changes on \"{$task->title}\".",
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string}
     */
    private function updateDirectly(Task $task, array $params): array
    {
        if (! $this->user?->can('update', $task)) {
            return ['success' => false, 'message' => 'You are not allowed to update this task status.'];
        }

        if (
            ! $this->user->canManageProject($task->project)
            && in_array($task->status, [TaskStatus::Review, TaskStatus::Done], true)
        ) {
            return ['success' => false, 'message' => 'Assignees can only change tasks that are still in todo or in progress.'];
        }

        $originalStatus = $task->status;
        $task->update([
            'status' => TaskStatus::from((string) $params['status']),
        ]);
        $task->refresh();
        $task->recordStatusChange($this->user, $originalStatus, $task->status);

        return [
            'success' => true,
            'message' => "Updated \"{$task->title}\" to {$task->status->value}.",
        ];
    }
}
