<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaskActivityType::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function message(): string
    {
        $meta = $this->meta ?? [];
        $actor = $this->user?->name ?? __('tasks.activity.system');

        return match ($this->type) {
            TaskActivityType::TaskCreated => __('tasks.activity.messages.created', [
                'actor' => $actor,
            ]),
            TaskActivityType::TaskStatusChanged => __('tasks.activity.messages.status_changed', [
                'actor' => $actor,
                'from' => __('tasks.statuses.'.$meta['from_status']),
                'to' => __('tasks.statuses.'.$meta['to_status']),
            ]),
            TaskActivityType::TaskAssigned => $this->assignmentMessage($actor, $meta),
            TaskActivityType::DeliverableSubmitted => __('tasks.activity.messages.deliverable_submitted', [
                'actor' => $actor,
            ]),
            TaskActivityType::DeliverableApproved => __('tasks.activity.messages.deliverable_approved', [
                'actor' => $actor,
            ]),
            TaskActivityType::ChangesRequested => __('tasks.activity.messages.changes_requested', [
                'actor' => $actor,
            ]),
            TaskActivityType::CommentAdded => __('tasks.activity.messages.comment_added', [
                'actor' => $actor,
                'comment' => $meta['comment_excerpt'] ?? '',
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function assignmentMessage(string $actor, array $meta): string
    {
        $from = $meta['from_assignee_name'] ?? null;
        $to = $meta['to_assignee_name'] ?? null;

        if ($from && $to) {
            return __('tasks.activity.messages.reassigned', [
                'actor' => $actor,
                'from' => $from,
                'to' => $to,
            ]);
        }

        if ($to) {
            return __('tasks.activity.messages.assigned', [
                'actor' => $actor,
                'to' => $to,
            ]);
        }

        return __('tasks.activity.messages.unassigned', [
            'actor' => $actor,
            'from' => $from ?: __('tasks.unassigned'),
        ]);
    }
}
