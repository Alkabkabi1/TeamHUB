<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
*/

// Send "event starts in 24h" reminders to registered attendees.
Schedule::command('events:send-reminders')->hourly();

// Auto-issue certificates to checked-in attendees once an activity has ended.
Schedule::command('certificates:issue-due')->hourly();

// Prune certificates older than one year (REQ-13 retention).
Schedule::command('certificates:prune')->dailyAt('02:00');

// Staging only: rebuild the demo dataset every hour. Gated by the
// DEMO_HOURLY_RESET flag (config/demo.php) so it never runs in production,
// where the same nixpacks image is deployed without the flag set.
Schedule::command('demo:reset')
    ->hourly()
    ->when(fn (): bool => (bool) config('demo.hourly_reset') && ! app()->isProduction());
