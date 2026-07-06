<?php

namespace App\Notifications;

use App\Models\ProjectUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPostNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ProjectUpdate $post) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->post->workspace;

        return (new MailMessage)
            ->subject(__('news.notification.subject', [
                'title' => $this->post->title,
                'workspace' => $workspace->name,
            ]))
            ->greeting(__('news.notification.greeting'))
            ->line(__('news.notification.body', [
                'title' => $this->post->title,
                'workspace' => $workspace->name,
            ]))
            ->action(
                __('news.notification.action'),
                $this->post->project_id
                    ? route('projects.show', [$workspace, $this->post->project_id])
                    : route('workspaces.show', $workspace),
            )
            ->line(__('news.notification.footer', ['workspace' => $workspace->name]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'workspace_id' => $this->post->workspace_id,
        ];
    }
}
