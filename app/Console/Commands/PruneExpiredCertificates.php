<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneExpiredCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete certificates older than 1 year and remove their stored PDF files.';

    public function handle(): int
    {
        $expired = Certificate::expired()->get();

        if ($expired->isEmpty()) {
            $this->info('No expired certificates found.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expired as $certificate) {
            if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
                Storage::disk('public')->delete($certificate->file_path);
            }

            $certificate->delete();
            $count++;
        }

        $this->info("Pruned {$count} expired certificate(s).");

        return self::SUCCESS;
    }
}
