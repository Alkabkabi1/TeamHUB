<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $sender,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.admin_message.mail_subject', [
                'sender' => $this->sender->name,
            ]))
            ->greeting(__('notifications.admin_message.title'))
            ->line(__('notifications.admin_message.body', [
                'sender' => $this->sender->name,
                'message' => $this->message,
            ]))
            ->action(
                __('notifications.admin_message.action'),
                route('dashboard', absolute: true),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.admin_message.title'),
            'body' => __('notifications.admin_message.body', [
                'sender' => $this->sender->name,
                'message' => $this->message,
            ]),
            'action_label' => __('notifications.admin_message.action'),
            'action_url' => route('dashboard', absolute: false),
            'kind' => 'admin_message',
        ];
    }
}
