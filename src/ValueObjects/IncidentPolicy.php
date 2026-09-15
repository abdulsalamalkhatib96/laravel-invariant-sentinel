<?php

namespace Evolvex\InvariantSentinel\ValueObjects;

final readonly class IncidentPolicy
{
    public function __construct(
        public int $openAfterFailures = 1,
        public int $resolveAfterPasses = 1,
    ) {
        if ($openAfterFailures < 1 || $resolveAfterPasses < 1) {
            throw new \InvalidArgumentException('Incident thresholds must be at least 1.');
        }
    }

    public static function make(int $openAfterFailures = 1, int $resolveAfterPasses = 1): self
    {
        return new self($openAfterFailures, $resolveAfterPasses);
    }
}
