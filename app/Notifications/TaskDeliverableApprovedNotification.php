<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeliverableApprovedNotification extends Notification implements ShouldQueue
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
            ->subject(__('notifications.task_deliverable_approved.mail_subject', [
                'task' => $this->task->title,
            ]))
            ->greeting(__('notifications.task_deliverable_approved.title'))
            ->line(__('notifications.task_deliverable_approved.body', [
                'actor' => $this->actor->name,
                'task' => $this->task->title,
            ]))
            ->action(
                __('notifications.task_deliverable_approved.action'),
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
            'title' => __('notifications.task_deliverable_approved.title'),
            'body' => __('notifications.task_deliverable_approved.body', [
                'actor' => $this->actor->name,
                'task' => $this->task->title,
            ]),
            'action_label' => __('notifications.task_deliverable_approved.action'),
            'action_url' => route('committees.tasks.show', [$this->task->committee->club_id, $this->task->committee_id, $this->task], absolute: false),
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'kind' => 'task_deliverable_approved',
        ];
    }
}
