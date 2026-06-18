<?php

namespace App\Notifications;

use App\Models\ClubJoinApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinApplicationReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ClubJoinApplication $application) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $club = $this->application->club;

        return (new MailMessage)
            ->subject(__('join.notification.received.subject', ['club' => $club->name]))
            ->greeting(__('join.notification.received.greeting', ['name' => $notifiable->name]))
            ->line(__('join.notification.received.body', [
                'applicant' => $this->application->full_name,
                'club' => $club->name,
            ]))
            ->action(__('join.notification.received.action'), route('clubs.manage', $club))
            ->line(__('join.notification.received.footer', ['club' => $club->name]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'club_id' => $this->application->club_id,
            'applicant' => $this->application->full_name,
        ];
    }
}
