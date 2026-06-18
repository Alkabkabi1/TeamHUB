<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Notifications\CertificateIssuedNotification;
use App\Services\CertificateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('certificates:issue-due')]
#[Description('Auto-issue certificates to checked-in attendees of ended activities whose club has an active default template.')]
class IssueDueCertificates extends Command
{
    public function handle(CertificateService $certificates): int
    {
        // Activities that have finished: ended in the past, or (when no end is
        // set) started in the past.
        $events = Event::query()
            ->with('club')
            ->whereNotNull('starts_at')
            ->where(function ($query): void {
                $query->where('ends_at', '<', now())
                    ->orWhere(function ($inner): void {
                        $inner->whereNull('ends_at')->where('starts_at', '<', now());
                    });
            })
            ->get();

        $issued = 0;

        foreach ($events as $event) {
            $club = $event->club;

            if ($club === null || $club->defaultCertificateTemplate() === null) {
                continue;
            }

            $alreadyIssued = Certificate::query()
                ->where('event_id', $event->id)
                ->pluck('user_id')
                ->all();

            $attendances = EventAttendance::query()
                ->where('event_id', $event->id)
                ->whereIn('status', ['checked_in', 'approved'])
                ->whereNotIn('user_id', $alreadyIssued)
                ->with('user')
                ->get();

            foreach ($attendances as $attendance) {
                if ($attendance->user === null) {
                    continue;
                }

                try {
                    $certificate = $certificates->issue(
                        user: $attendance->user,
                        club: $club,
                        event: $event,
                        attendance: $attendance,
                    );
                } catch (RuntimeException) {
                    continue;
                }

                if ($certificate->wasRecentlyCreated) {
                    $attendance->user->notify(new CertificateIssuedNotification($certificate));
                    $issued++;
                }
            }
        }

        $this->info("Issued {$issued} certificate(s).");

        return self::SUCCESS;
    }
}
