<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Event $event) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('events.reminder.subject', ['title' => $this->event->title]))
            ->greeting(__('events.reminder.greeting'))
            ->line(__('events.reminder.body', [
                'title' => $this->event->title,
                'date' => $this->event->starts_at?->format('Y-m-d H:i') ?? '',
                'location' => $this->event->location ?? __('events.location_tbd'),
            ]))
            ->action(__('events.reminder.action'), route('events'))
            ->line(__('events.reminder.footer'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'starts_at' => $this->event->starts_at?->toIso8601String(),
        ];
    }
}
