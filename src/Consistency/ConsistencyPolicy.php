<?php

namespace Evolvex\InvariantSentinel\Consistency;

use DateTimeInterface;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;

abstract readonly class ConsistencyPolicy
{
    abstract public function classify(EvaluationStatus $rawStatus, ?DateTimeInterface $firstFailureAt, DateTimeInterface $now): EvaluationStatus;

    public function retryAfterSeconds(): ?int
    {
        return null;
    }

    public static function immediate(): Immediate
    {
        return new Immediate();
    }

    public static function eventuallyWithin(int $seconds, int $retryAfterSeconds = 5): EventuallyWithin
    {
        return new EventuallyWithin($seconds, $retryAfterSeconds);
    }

    public static function stableFor(int $seconds, int $retryAfterSeconds = 5): StableFor
    {
        return new StableFor($seconds, $retryAfterSeconds);
    }
}
