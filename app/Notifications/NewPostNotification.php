<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPostNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Post $post) {}

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
            ->subject(__('news.notification.subject', [
                'title' => $this->post->title,
                'club' => $this->post->club->name,
            ]))
            ->greeting(__('news.notification.greeting'))
            ->line(__('news.notification.body', [
                'title' => $this->post->title,
                'club' => $this->post->club->name,
            ]))
            ->action(__('news.notification.action'), route('news.show', $this->post))
            ->line(__('news.notification.footer', ['club' => $this->post->club->name]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'club_id' => $this->post->club_id,
        ];
    }
}
