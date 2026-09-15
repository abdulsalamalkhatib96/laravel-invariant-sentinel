<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\Queue\PendingCheckDispatcher;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Console\Command;

final class RecheckCommand extends Command
{
    protected $signature = 'sentinel:recheck {incident}';
    protected $description = 'Schedule a recheck for an incident';
    public function handle(IncidentStore $store, PendingCheckDispatcher $dispatcher): int
    {
        $i = $store->find($this->argument('incident')) ?? throw new \InvalidArgumentException('Incident not found.');
        $dispatcher->schedule($i->invariant_key, SubjectRef::make($i->subject_type, $i->subject_id, $i->tenant_key), TriggerType::Recheck);
        $this->info('Recheck scheduled.');
        return self::SUCCESS;
    }
}
