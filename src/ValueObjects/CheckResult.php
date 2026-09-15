<?php

namespace Evolvex\InvariantSentinel\ValueObjects;

use Evolvex\InvariantSentinel\Enums\EvaluationStatus;
use Throwable;

final readonly class CheckResult
{
    /** @param list<Evidence> $evidence */
    public function __construct(
        public string $ruleKey,
        public EvaluationStatus $status,
        public ?string $code = null,
        public ?string $message = null,
        public mixed $expected = null,
        public mixed $actual = null,
        public array $evidence = [],
        public array $meta = [],
    ) {}

    public static function pass(string $ruleKey, ?string $message = null, array $evidence = []): self
    {
        return new self($ruleKey, EvaluationStatus::Pass, message: $message, evidence: $evidence);
    }

    public static function fail(string $ruleKey, string $code, mixed $expected = null, mixed $actual = null, ?string $message = null, array $evidence = []): self
    {
        return new self($ruleKey, EvaluationStatus::Fail, $code, $message, $expected, $actual, $evidence);
    }

    public static function unknown(string $ruleKey, string $code, ?string $message = null, array $evidence = []): self
    {
        return new self($ruleKey, EvaluationStatus::Unknown, $code, $message, evidence: $evidence);
    }

    public static function error(string $ruleKey, Throwable|string $error): self
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;
        return new self($ruleKey, EvaluationStatus::Error, 'sentinel.rule_error', $message);
    }

    public function toArray(): array
    {
        return [
            'rule_key' => $this->ruleKey,
            'status' => $this->status->value,
            'code' => $this->code,
            'message' => $this->message,
            'expected' => $this->expected,
            'actual' => $this->actual,
            'evidence' => array_map(fn (Evidence $e) => $e->toArray(), $this->evidence),
            'meta' => $this->meta,
        ];
    }
}
