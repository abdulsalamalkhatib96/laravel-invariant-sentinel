<?php

namespace Evolvex\InvariantSentinel\Evaluation;

use Evolvex\InvariantSentinel\Contracts\FactStore;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

final class EvaluationContext
{
    public function __construct(
        public SubjectRef $subject,
        public array $context,
        public EvaluationBudget $budget,
        public FactStore $facts,
    ) {}

    private int $externalCalls = 0;

    public function externalCall(callable $operation): mixed
    {
        $this->externalCalls++;
        if ($this->externalCalls > $this->budget->maxExternalCalls) {
            throw new \Evolvex\InvariantSentinel\Exceptions\EvaluationBudgetExceeded('External-call budget exceeded.');
        }
        return $operation();
    }

    public function externalCalls(): int { return $this->externalCalls; }
}
