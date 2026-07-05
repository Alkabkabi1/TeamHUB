<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Workspace $workspace) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('join.notification.rejected.subject', ['workspace' => $this->workspace->name]))
            ->greeting(__('join.notification.rejected.greeting', ['name' => $notifiable->name]))
            ->line(__('join.notification.rejected.body', ['workspace' => $this->workspace->name]))
            ->action(__('join.notification.rejected.action'), route('dashboard'))
            ->line(__('join.notification.rejected.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->name,
            'decision' => 'rejected',
        ];
    }
}
