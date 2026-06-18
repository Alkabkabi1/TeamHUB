<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventUpdatedNotification extends Notification implements ShouldQueue
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
            ->subject(__('events.updated_notification.subject', ['title' => $this->event->title]))
            ->greeting(__('events.updated_notification.greeting', ['name' => $notifiable->name]))
            ->line(__('events.updated_notification.body', [
                'title' => $this->event->title,
                'date' => $this->event->starts_at?->format('Y-m-d H:i') ?? '',
                'location' => $this->event->location ?? __('events.location_tbd'),
            ]))
            ->action(__('events.updated_notification.action'), route('events.show', $this->event))
            ->line(__('events.updated_notification.footer'));
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
