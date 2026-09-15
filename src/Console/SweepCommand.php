<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Sweep\SweepEngine;
use Illuminate\Console\Command;

final class SweepCommand extends Command
{
    protected $signature = 'sentinel:sweep {invariant?}';
    protected $description = 'Schedule anti-entropy invariant sweeps';
    public function handle(SweepEngine $sweeps): int
    {
        $counts = $sweeps->sweep($this->argument('invariant'));
        foreach ($counts as $key => $count) $this->line("{$key}: {$count} candidate(s)");
        return self::SUCCESS;
    }
}
