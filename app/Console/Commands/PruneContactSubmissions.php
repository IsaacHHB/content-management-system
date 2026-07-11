<?php

namespace App\Console\Commands;

use App\Models\ContactSubmission;
use Illuminate\Console\Command;

class PruneContactSubmissions extends Command
{
    protected $signature = 'contact:prune';

    protected $description = 'Delete contact submissions older than 24 months';

    public function handle(): int
    {
        $count = ContactSubmission::where('created_at', '<', now()->subMonths(24))->delete();
        $this->info("Pruned {$count} contact submission(s).");

        return self::SUCCESS;
    }
}
