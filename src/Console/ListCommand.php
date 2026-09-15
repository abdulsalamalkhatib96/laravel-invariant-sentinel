<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Illuminate\Console\Command;

final class ListCommand extends Command
{
    protected $signature = 'sentinel:list';
    protected $description = 'List registered business invariants';
    public function handle(InvariantRegistry $registry): int
    {
        $rows = [];
        foreach ($registry->all() as $key => $invariant) $rows[] = [$key, $invariant::version(), $invariant->severity()->value, $invariant::class];
        $this->table(['Key', 'Version', 'Severity', 'Class'], $rows);
        return self::SUCCESS;
    }
}
