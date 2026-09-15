<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Incidents\IncidentService;
use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Illuminate\Console\Command;

final class IncidentActionCommand extends Command
{
    protected $signature = 'sentinel:incident {id} {action? : ack|resolve} {--actor=} {--reason=manual}';
    protected $description = 'Acknowledge or manually resolve an incident';
    public function handle(IncidentService $service, IncidentStore $store): int
    {
        if (! $this->argument('action')) {
            $i = $store->find($this->argument('id')) ?? throw new \InvalidArgumentException('Incident not found.');
            $this->table(['Field','Value'], [
                ['ID',$i->id], ['Status',$i->status], ['Severity',$i->severity], ['Invariant',$i->invariant_key],
                ['Subject',$i->subject_type.':'.$i->subject_id], ['Rules',implode(', ', $i->rule_keys ?: [])],
                ['Opened',(string) $i->opened_at], ['Resolved',(string) ($i->resolved_at ?: '-')],
            ]);
            return self::SUCCESS;
        }
        $i = match ($this->argument('action')) {
            'ack' => $service->acknowledge($this->argument('id'), $this->option('actor')),
            'resolve' => $service->resolve($this->argument('id'), $this->option('reason'), $this->option('actor')),
            default => throw new \InvalidArgumentException('Action must be ack or resolve.'),
        };
        $this->info("Incident {$i->id}: {$i->status}");
        return self::SUCCESS;
    }
}
