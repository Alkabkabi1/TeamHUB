<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Task;

class TaskPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function listItem(Task $task): array
    {
        $assigneeName = $task->assignee?->name ?? __('dashboard.unassigned');

        return [
            'id' => $task->id,
            'title' => $task->title,
            'project' => $task->project?->name ?? '',
            'priority' => $task->priority->value,
            'dueDate' => $task->due_at?->toDateString() ?? '',
            'dueLabel' => $task->due_at
                ? $task->due_at->locale(app()->getLocale())->translatedFormat('j M')
                : '—',
            'status' => $task->status->value,
            'assignee' => [
                'name' => $assigneeName,
                'initials' => $this->initials($assigneeName),
            ],
            'url' => route('tasks.show', $task, absolute: false),
            'project_id' => $task->project_id,
            'workspace_id' => $task->project?->workspace_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function staffDashboardItem(Task $task): array
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
            'project' => $task->project?->name ?? '',
            'workspace_id' => $task->project?->workspace_id,
            'project_id' => $task->project_id,
            'has_deliverable' => $hasDeliverable,
            'deliverable_url' => $task->deliverable_url,
            'deliverable_notes' => $task->deliverable_notes,
            'can_submit' => in_array($task->status, [TaskStatus::Todo, TaskStatus::InProgress], true),
            'detail_url' => route('tasks.show', $task, absolute: false),
            'submit_url' => route('tasks.deliverable', $task, absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'status_label' => __($task->status->label()),
            'priority' => $task->priority->value,
            'priority_label' => __($task->priority->label()),
            'due_at' => $task->due_at?->toIso8601String(),
            'assignee_name' => $task->assignee?->name,
            'creator_name' => $task->creator?->name,
            'has_deliverable' => $this->hasDeliverable($task),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Task $task): array
    {
        $deliverableFile = $task->getFirstMedia(Task::DELIVERABLE_COLLECTION);

        return [
            ...$this->summary($task),
            'description' => $task->description,
            'assignee_id' => $task->assigned_to,
            'deliverable_url' => $task->deliverable_url,
            'deliverable_notes' => $task->deliverable_notes,
            'submitted_for_review_at' => $task->submitted_for_review_at?->toIso8601String(),
            'reviewed_at' => $task->reviewed_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'review_notes' => $task->review_notes,
            'creator' => $task->creator ? ['id' => $task->creator->id, 'name' => $task->creator->name] : null,
            'assignee' => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            'reviewer' => $task->reviewer ? ['id' => $task->reviewer->id, 'name' => $task->reviewer->name] : null,
            'deliverable_file' => $deliverableFile ? [
                'name' => $deliverableFile->file_name,
                'url' => $deliverableFile->getUrl(),
            ] : null,
        ];
    }

    private function hasDeliverable(Task $task): bool
    {
        return $task->getFirstMedia(Task::DELIVERABLE_COLLECTION) !== null
            || filled($task->deliverable_url)
            || filled($task->deliverable_notes);
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr(end($parts), 0, 1));
    }
}
