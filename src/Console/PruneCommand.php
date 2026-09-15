<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Contracts\ObservationStore;
use Illuminate\Console\Command;

final class PruneCommand extends Command
{
    protected $signature = 'sentinel:prune {--days=}';
    protected $description = 'Prune old invariant observations';
    public function handle(ObservationStore $store): int
    {
        $days = (int) ($this->option('days') ?: config('sentinel.evidence.retention_days', 30));
        $count = $store->pruneBefore(now()->subDays($days));
        $this->info("Pruned {$count} observation(s).");
        return self::SUCCESS;
    }
}
