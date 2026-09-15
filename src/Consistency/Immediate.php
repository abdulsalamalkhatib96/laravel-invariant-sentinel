<?php

namespace Evolvex\InvariantSentinel\Consistency;

use DateTimeInterface;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;

final readonly class Immediate extends ConsistencyPolicy
{
    public function classify(EvaluationStatus $rawStatus, ?DateTimeInterface $firstFailureAt, DateTimeInterface $now): EvaluationStatus
    {
        return $rawStatus;
    }
}
