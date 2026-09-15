<?php

namespace Evolvex\InvariantSentinel\Remediation;

final readonly class RemediationResult
{
    public function __construct(public bool $successful, public string $message, public array $metadata = []) {}
}
