<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset')]
#[Description('DESTRUCTIVE: drop, re-migrate and re-seed the database to a fresh demo dataset. Staging only.')]
class ResetDemoData extends Command
{
    /**
     * Rebuild the database from scratch so the staging demo always returns to a
     * known, fully-seeded state.
     *
     * Two independent guards keep this away from production data: it aborts in
     * the production environment, and it only runs when the staging-only
     * `DEMO_HOURLY_RESET` flag is enabled (see config/demo.php).
     */
    public function handle(): int
    {
        if ($this->getLaravel()->isProduction()) {
            $this->error('demo:reset refuses to run in the production environment.');

            return self::FAILURE;
        }

        if (! config('demo.hourly_reset')) {
            $this->warn('demo:reset skipped: DEMO_HOURLY_RESET is not enabled.');

            return self::SUCCESS;
        }

        $this->components->info('Resetting demo data (migrate:fresh --seed)…');

        return $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);
    }
}
