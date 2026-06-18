<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Certificate $certificate) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->certificate->loadMissing('event', 'club');

        $subject = $this->certificate->event?->title
            ?? $this->certificate->title
            ?? __('certificates.certificate_of_participation');
        $clubName = $this->certificate->club?->name ?? __('certificates.clubs_platform');

        return (new MailMessage)
            ->subject(__('certificates.notification.subject', ['event' => $subject]))
            ->greeting(__('certificates.notification.greeting', ['name' => $notifiable->name]))
            ->line(__('certificates.notification.body', [
                'event' => $subject,
                'club' => $clubName,
            ]))
            ->action(__('certificates.notification.action'), route('certificates.download', $this->certificate))
            ->line(__('certificates.notification.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'certificate_id' => $this->certificate->id,
            'certificate_no' => $this->certificate->certificate_no,
        ];
    }
}
