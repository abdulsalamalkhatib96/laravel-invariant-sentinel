<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Queue\DrainPendingChecksJob;
use Illuminate\Console\Command;

final class DrainCommand extends Command
{
    protected $signature = 'sentinel:drain';
    protected $description = 'Drain due invariant checks synchronously';
    public function handle(DrainPendingChecksJob $job): int { app()->call([$job, 'handle']); $this->info('Due checks drained.'); return self::SUCCESS; }
}
