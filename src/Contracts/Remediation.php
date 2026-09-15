<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Remediation\RemediationPlan;
use Evolvex\InvariantSentinel\Remediation\RemediationResult;
use Evolvex\InvariantSentinel\Models\Incident;

interface Remediation
{
    public function plan(Incident $incident): RemediationPlan;
    public function execute(RemediationPlan $plan): RemediationResult;
}
