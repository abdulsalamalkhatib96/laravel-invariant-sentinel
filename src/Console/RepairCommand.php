<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Evolvex\InvariantSentinel\Remediation\RemediationRunner;
use Evolvex\InvariantSentinel\Queue\PendingCheckDispatcher;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Console\Command;

final class RepairCommand extends Command
{
    protected $signature = 'sentinel:repair {incident} {--execute}';
    protected $description = 'Preview or execute a safe invariant remediation';
    public function handle(IncidentStore $store, RemediationRunner $runner, PendingCheckDispatcher $dispatcher): int
    {
        $incident = $store->find($this->argument('incident')) ?? throw new \InvalidArgumentException('Incident not found.');
        $plan = $runner->plan($incident);
        $this->line($plan->summary);
        foreach ($plan->steps as $step) $this->line(' - '.(is_string($step) ? $step : json_encode($step)));
        if (! $this->option('execute')) { $this->comment('Dry-run only. Pass --execute to run after reviewing the plan.'); return self::SUCCESS; }
        if (! $this->confirm('Execute this remediation plan?')) return self::SUCCESS;
        $result = $runner->execute($incident, $plan);
        $this->{$result->successful ? 'info' : 'error'}($result->message);
        if ($result->successful) {
            $dispatcher->schedule($incident->invariant_key, SubjectRef::make($incident->subject_type, $incident->subject_id, $incident->tenant_key), TriggerType::Recheck);
            $this->comment('Post-remediation invariant recheck scheduled.');
        }
        return $result->successful ? self::SUCCESS : self::FAILURE;
    }
}
