<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApprovedNotification extends Notification implements ShouldQueue
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
            ->subject(__('join.notification.approved.subject', ['workspace' => $this->workspace->name]))
            ->greeting(__('join.notification.approved.greeting', ['name' => $notifiable->name]))
            ->line(__('join.notification.approved.body', ['workspace' => $this->workspace->name]))
            ->action(__('join.notification.approved.action'), route('workspaces.show', $this->workspace))
            ->line(__('join.notification.approved.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->name,
            'decision' => 'approved',
        ];
    }
}
