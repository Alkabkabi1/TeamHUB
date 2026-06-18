<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Event $event) {}

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
            ->subject(__('events.cancelled_notification.subject', ['title' => $this->event->title]))
            ->greeting(__('events.cancelled_notification.greeting', ['name' => $notifiable->name]))
            ->line(__('events.cancelled_notification.body', [
                'title' => $this->event->title,
                'date' => $this->event->starts_at?->format('Y-m-d H:i') ?? '',
            ]))
            ->action(__('events.cancelled_notification.action'), route('events'))
            ->line(__('events.cancelled_notification.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
        ];
    }
}
