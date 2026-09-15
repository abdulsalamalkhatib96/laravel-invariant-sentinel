<?php

namespace Evolvex\InvariantSentinel\Remediation;

use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Contracts\RemediableInvariant;
use Evolvex\InvariantSentinel\Models\Incident;

final class RemediationRunner
{
    public function __construct(private readonly InvariantRegistry $registry) {}

    public function plan(Incident $incident): RemediationPlan
    {
        $invariant = $this->registry->get($incident->invariant_key);
        if (! $invariant instanceof RemediableInvariant) throw new \LogicException('Invariant does not provide remediation.');
        return $invariant->remediation()->plan($incident);
    }

    public function execute(Incident $incident, RemediationPlan $plan): RemediationResult
    {
        if (! config('sentinel.remediation.enabled', false)) throw new \LogicException('Sentinel remediation is disabled.');
        $invariant = $this->registry->get($incident->invariant_key);
        if (! $invariant instanceof RemediableInvariant) throw new \LogicException('Invariant does not provide remediation.');
        return $invariant->remediation()->execute($plan);
    }
}
