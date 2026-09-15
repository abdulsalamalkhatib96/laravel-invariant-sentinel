<?php

namespace Evolvex\InvariantSentinel\Evaluation;

final readonly class EvaluationBudget
{
    public function __construct(
        public int $maxQueries = 50,
        public int $maxDurationMs = 3000,
        public int $maxExternalCalls = 0,
    ) {}
}
