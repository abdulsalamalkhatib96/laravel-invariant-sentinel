<?php

namespace Evolvex\InvariantSentinel\ValueObjects;

use DateTimeInterface;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;

final readonly class EvaluationResult
{
    /** @param list<CheckResult> $checks */
    public function __construct(
        public string $evaluationId,
        public string $invariantKey,
        public int $invariantVersion,
        public SubjectRef $subject,
        public EvaluationStatus $status,
        public array $checks,
        public int $durationMs,
        public ?string $incidentId = null,
        public ?DateTimeInterface $nextCheckAt = null,
        public array $context = [],
    ) {}

    public function failedRuleKeys(): array
    {
        return array_values(array_map(
            fn (CheckResult $r) => $r->ruleKey,
            array_filter($this->checks, fn (CheckResult $r) => $r->status === EvaluationStatus::Fail)
        ));
    }

    public function isHealthy(): bool
    {
        return $this->status === EvaluationStatus::Pass;
    }
}
