<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Illuminate\Console\Command;

final class ShowCommand extends Command
{
    protected $signature = 'sentinel:show {invariant}';
    protected $description = 'Show a registered invariant';
    public function handle(InvariantRegistry $registry): int
    {
        $i = $registry->get($this->argument('invariant'));
        $this->line('Key: '.$i::key());
        $this->line('Class: '.$i::class);
        $this->line('Version: '.$i::version());
        $this->line('Severity: '.$i->severity()->value);
        $this->line('Checks: '.implode(', ', array_map(fn ($c) => $c->key(), $i->checks())));
        return self::SUCCESS;
    }
}
