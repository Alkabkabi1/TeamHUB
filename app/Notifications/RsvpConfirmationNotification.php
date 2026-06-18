<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RsvpConfirmationNotification extends Notification implements ShouldQueue
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
            ->subject(__('events.rsvp_confirmation.subject', ['title' => $this->event->title]))
            ->greeting(__('events.rsvp_confirmation.greeting', ['name' => $notifiable->name]))
            ->line(__('events.rsvp_confirmation.body', [
                'title' => $this->event->title,
                'date' => $this->event->starts_at?->format('Y-m-d H:i') ?? '',
                'location' => $this->event->location ?? __('events.location_tbd'),
            ]))
            ->action(__('events.rsvp_confirmation.action'), route('events.show', $this->event))
            ->line(__('events.rsvp_confirmation.footer'));
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
