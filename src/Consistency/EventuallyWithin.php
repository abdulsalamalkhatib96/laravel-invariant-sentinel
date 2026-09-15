<?php

namespace Evolvex\InvariantSentinel\Consistency;

use DateTimeInterface;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;

final readonly class EventuallyWithin extends ConsistencyPolicy
{
    public function __construct(public int $seconds, public int $retrySeconds = 5)
    {
        if ($seconds < 1 || $retrySeconds < 1) {
            throw new \InvalidArgumentException('Consistency windows must be positive.');
        }
    }

    public function classify(EvaluationStatus $rawStatus, ?DateTimeInterface $firstFailureAt, DateTimeInterface $now): EvaluationStatus
    {
        if ($rawStatus !== EvaluationStatus::Fail) {
            return $rawStatus;
        }

        if ($firstFailureAt === null || ($now->getTimestamp() - $firstFailureAt->getTimestamp()) < $this->seconds) {
            return EvaluationStatus::Deferred;
        }

        return EvaluationStatus::Fail;
    }

    public function retryAfterSeconds(): ?int
    {
        return $this->retrySeconds;
    }
}
