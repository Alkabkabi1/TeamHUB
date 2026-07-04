<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
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
        $this->task->loadMissing('committee.club');

        return (new MailMessage)
            ->subject(__('notifications.task_assigned.mail_subject', [
                'task' => $this->task->title,
            ]))
            ->greeting(__('notifications.task_assigned.title'))
            ->line(__('notifications.task_assigned.body', [
                'actor' => $this->actor->name,
                'task' => $this->task->title,
                'project' => $this->task->committee->name,
            ]))
            ->action(
                __('notifications.task_assigned.action'),
                route('committees.tasks.show', [$this->task->committee->club_id, $this->task->committee_id, $this->task]),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->task->loadMissing('committee.club');

        return [
            'title' => __('notifications.task_assigned.title'),
            'body' => __('notifications.task_assigned.body', [
                'actor' => $this->actor->name,
                'task' => $this->task->title,
                'project' => $this->task->committee->name,
            ]),
            'action_label' => __('notifications.task_assigned.action'),
            'action_url' => route('committees.tasks.show', [$this->task->committee->club_id, $this->task->committee_id, $this->task], absolute: false),
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'kind' => 'task_assigned',
        ];
    }
}
