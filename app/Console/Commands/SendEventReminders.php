<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Notifications\EventReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('events:send-reminders')]
#[Description('Send 24-hour reminder notifications to attendees of upcoming events.')]
class SendEventReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $windowStart = now();
        $windowEnd = now()->addHours(24);

        $events = Event::query()
            ->where('status', 'active')
            ->where('starts_at', '>', $windowStart)
            ->where('starts_at', '<=', $windowEnd)
            ->get();

        $notified = 0;

        foreach ($events as $event) {
            EventAttendance::query()
                ->where('event_id', $event->id)
                ->whereIn('status', ['registered', 'approved'])
                ->whereNull('reminder_sent_at')
                ->with('user')
                ->get()
                ->each(function (EventAttendance $attendance) use ($event, &$notified): void {
                    if ($attendance->user !== null) {
                        $attendance->user->notify(new EventReminderNotification($event));
                        $notified++;
                    }

                    // Stamp regardless so the row is never picked up again, even
                    // if the attendee was somehow detached.
                    $attendance->forceFill(['reminder_sent_at' => now()])->save();
                });
        }

        $this->info("Sent {$notified} reminder(s) for {$events->count()} event(s).");

        return self::SUCCESS;
    }
}
