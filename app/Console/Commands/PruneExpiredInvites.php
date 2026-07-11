<?php

namespace App\Console\Commands;

use App\Models\Invite;
use Illuminate\Console\Command;

class PruneExpiredInvites extends Command
{
    protected $signature = 'invites:prune';

    protected $description = 'Remove expired, unaccepted administrator invitations';

    public function handle(): int
    {
        $count = Invite::whereNull('accepted_at')->where('expires_at', '<=', now())->delete();
        $this->info("Pruned {$count} expired invite(s).");

        return self::SUCCESS;
    }
}
