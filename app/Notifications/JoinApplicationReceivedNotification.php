<?php

namespace App\Notifications;

use App\Models\WorkspaceMembershipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinApplicationReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly WorkspaceMembershipRequest $application) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->application->workspace;

        return (new MailMessage)
            ->subject(__('join.notification.received.subject', ['club' => $workspace->name]))
            ->greeting(__('join.notification.received.greeting', ['name' => $notifiable->name]))
            ->line(__('join.notification.received.body', [
                'applicant' => $this->application->full_name,
                'club' => $workspace->name,
            ]))
            ->action(__('join.notification.received.action'), route('workspaces.manage', $workspace))
            ->line(__('join.notification.received.footer', ['club' => $workspace->name]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'workspace_id' => $this->application->workspace_id,
            'applicant' => $this->application->full_name,
        ];
    }
}
