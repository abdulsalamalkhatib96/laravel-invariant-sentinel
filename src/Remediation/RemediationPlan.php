<?php

namespace Evolvex\InvariantSentinel\Remediation;

final readonly class RemediationPlan
{
    public function __construct(public string $idempotencyKey, public string $summary, public array $steps = [], public array $metadata = []) {}
}
