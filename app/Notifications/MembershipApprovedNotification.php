<?php

namespace App\Notifications;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApprovedNotification extends Notification implements ShouldQueue
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
            ->subject(__('join.notification.approved.subject', ['club' => $this->club->name]))
            ->greeting(__('join.notification.approved.greeting', ['name' => $notifiable->name]))
            ->line(__('join.notification.approved.body', ['club' => $this->club->name]))
            ->action(__('join.notification.approved.action'), route('clubs.show', $this->club))
            ->line(__('join.notification.approved.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'decision' => 'approved',
        ];
    }
}
