<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use App\Notifications\EventCancelledNotification;
use App\Notifications\EventReminderNotification;
use App\Notifications\EventUpdatedNotification;
use App\Notifications\JoinApplicationReceivedNotification;
use App\Notifications\MembershipApprovedNotification;
use App\Notifications\MembershipRejectedNotification;
use App\Notifications\NewPostNotification;
use App\Notifications\RsvpConfirmationNotification;
use Illuminate\Console\Command;

class SendTestMails extends Command
{
    protected $signature = 'mail:test
        {email : The recipient address to send every email to}
        {--locale=en : Locale to render the emails in (en or ar)}';

    protected $description = 'Send one of every application email to an address so they can be inspected in Mailpit';

    public function handle(): int
    {
        $email = $this->argument('email');
        $locale = $this->option('locale');

        app()->setLocale($locale);

        $recipient = new User;
        $recipient->name = 'Test Student';
        $recipient->email = $email;
        // Drive the locale through the recipient's preference, since that is what
        // the notifications actually honour (HasLocalePreference) when sending.
        $recipient->locale = $locale;

        $club = $this->demoClub();
        $event = $this->demoEvent();
        $post = $this->demoPost($club);
        $certificate = $this->demoCertificate($event, $club);
        $application = $this->demoApplication($club);

        $emails = [
            'Event reminder' => new EventReminderNotification($event),
            'RSVP confirmation' => new RsvpConfirmationNotification($event),
            'Event cancelled' => new EventCancelledNotification($event),
            'Event updated' => new EventUpdatedNotification($event),
            'New post' => new NewPostNotification($post),
            'Join application received' => new JoinApplicationReceivedNotification($application),
            'Membership approved' => new MembershipApprovedNotification($club),
            'Membership rejected' => new MembershipRejectedNotification($club),
            'Certificate issued' => new CertificateIssuedNotification($certificate),
        ];

        foreach ($emails as $label => $notification) {
            // notifyNow bypasses the queue so every message lands in Mailpit immediately.
            $recipient->notifyNow($notification);
            $this->line("  <info>✓</info> {$label}");
        }

        $this->newLine();
        $this->info(count($emails)." emails sent to {$email} ({$locale}). Open http://localhost:8025/ to inspect.");

        return self::SUCCESS;
    }

    private function demoClub(): Club
    {
        $club = Club::query()->first();

        if (! $club) {
            $club = new Club;
            $club->id = 0;
            $club->name = 'Demo Club';
            $club->theme = '#006471';
        }

        return $club;
    }

    private function demoEvent(): Event
    {
        $event = Event::query()->first();

        if (! $event) {
            $event = new Event;
            $event->id = 0;
            $event->title = 'Demo Volunteer Day';
            $event->starts_at = now()->addDay();
            $event->location = 'Main Auditorium';
        }

        return $event;
    }

    private function demoPost(Club $club): Post
    {
        $post = Post::with('club')->first();

        if (! $post) {
            $post = new Post;
            $post->id = 0;
            $post->title = 'Demo Announcement';
            $post->setRelation('club', $club);
        }

        return $post;
    }

    private function demoCertificate(Event $event, Club $club): Certificate
    {
        $certificate = Certificate::with('attendance.event.club')->first();

        if ($certificate && $certificate->attendance?->event?->club) {
            return $certificate;
        }

        $event->setRelation('club', $club);

        $attendance = new EventAttendance;
        $attendance->id = 0;
        $attendance->setRelation('event', $event);

        $certificate = new Certificate;
        $certificate->id = 0;
        $certificate->certificate_no = 'CERT-DEMO0001';
        $certificate->file_path = 'certificates/demo.pdf';
        $certificate->setRelation('attendance', $attendance);

        return $certificate;
    }

    private function demoApplication(Club $club): ClubJoinApplication
    {
        $application = ClubJoinApplication::with('club')->first();

        if ($application && $application->club) {
            return $application;
        }

        $application = new ClubJoinApplication;
        $application->id = 0;
        $application->full_name = 'Test Applicant';
        $application->setRelation('club', $club);

        return $application;
    }
}
