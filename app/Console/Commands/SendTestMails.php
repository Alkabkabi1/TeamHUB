<?php

namespace App\Console\Commands;

use App\Models\ProjectUpdate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembershipRequest;
use App\Notifications\JoinApplicationReceivedNotification;
use App\Notifications\MembershipApprovedNotification;
use App\Notifications\MembershipRejectedNotification;
use App\Notifications\NewPostNotification;
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
        $recipient->locale = $locale;

        $workspace = $this->demoWorkspace();
        $post = $this->demoUpdate($workspace);
        $application = $this->demoApplication($workspace);

        $emails = [
            'New post' => new NewPostNotification($post),
            'Join application received' => new JoinApplicationReceivedNotification($application),
            'Membership approved' => new MembershipApprovedNotification($workspace),
            'Membership rejected' => new MembershipRejectedNotification($workspace),
        ];

        foreach ($emails as $label => $notification) {
            $recipient->notifyNow($notification);
            $this->line("  <info>✓</info> {$label}");
        }

        $this->newLine();
        $this->info(count($emails)." emails sent to {$email} ({$locale}). Open http://localhost:8025/ to inspect.");

        return self::SUCCESS;
    }

    private function demoWorkspace(): Workspace
    {
        $workspace = Workspace::query()->first();

        if (! $workspace) {
            $workspace = new Workspace;
            $workspace->id = 0;
            $workspace->name = 'Demo Workspace';
            $workspace->theme = '#c8924a';
        }

        return $workspace;
    }

    private function demoUpdate(Workspace $workspace): ProjectUpdate
    {
        $post = ProjectUpdate::with('workspace')->first();

        if (! $post) {
            $post = new ProjectUpdate;
            $post->id = 0;
            $post->title = 'Demo Announcement';
            $post->setRelation('workspace', $workspace);
        }

        return $post;
    }

    private function demoApplication(Workspace $workspace): WorkspaceMembershipRequest
    {
        $application = WorkspaceMembershipRequest::with('workspace')->first();

        if ($application && $application->workspace) {
            return $application;
        }

        $application = new WorkspaceMembershipRequest;
        $application->id = 0;
        $application->full_name = 'Test Applicant';
        $application->setRelation('workspace', $workspace);

        return $application;
    }
}
