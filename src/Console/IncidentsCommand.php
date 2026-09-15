<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Illuminate\Console\Command;

final class IncidentsCommand extends Command
{
    protected $signature = 'sentinel:incidents';
    protected $description = 'List open Sentinel incidents';
    public function handle(IncidentStore $store): int
    {
        $rows = [];
        foreach ($store->open() as $i) $rows[] = [$i->id, $i->severity, $i->invariant_key, $i->subject_type.':'.$i->subject_id, $i->status, $i->opened_at];
        $this->table(['ID', 'Severity', 'Invariant', 'Subject', 'Status', 'Opened'], $rows);
        return self::SUCCESS;
    }
}
