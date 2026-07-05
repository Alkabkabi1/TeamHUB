<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskSubmittedForReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->task->loadMissing('project.workspace');

        return (new MailMessage)
            ->subject(__('notifications.task_submitted_for_review.mail_subject', [
                'task' => $this->task->title,
            ]))
            ->greeting(__('notifications.task_submitted_for_review.title'))
            ->line(__('notifications.task_submitted_for_review.body', [
                'actor' => $this->actor->name,
                'task' => $this->task->title,
                'project' => $this->task->project->name,
            ]))
            ->action(
                __('notifications.task_submitted_for_review.action'),
                route('projects.tasks.show', [$this->task->project->workspace_id, $this->task->project_id, $this->task]),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->task->loadMissing('project.workspace');

        return [
            'title' => __('notifications.task_submitted_for_review.title'),
            'body' => __('notifications.task_submitted_for_review.body', [
                'actor' => $this->actor->name,
                'task' => $this->task->title,
                'project' => $this->task->project->name,
            ]),
            'action_label' => __('notifications.task_submitted_for_review.action'),
            'action_url' => route('projects.tasks.show', [$this->task->project->workspace_id, $this->task->project_id, $this->task], absolute: false),
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'kind' => 'task_submitted_for_review',
        ];
    }
}
