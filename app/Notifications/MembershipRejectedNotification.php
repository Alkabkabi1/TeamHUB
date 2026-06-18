<?php

namespace App\Notifications;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Club $club) {}

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
            ->subject(__('join.notification.rejected.subject', ['club' => $this->club->name]))
            ->greeting(__('join.notification.rejected.greeting', ['name' => $notifiable->name]))
            ->line(__('join.notification.rejected.body', ['club' => $this->club->name]))
            ->action(__('join.notification.rejected.action'), route('clubs'))
            ->line(__('join.notification.rejected.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'decision' => 'rejected',
        ];
    }
}
