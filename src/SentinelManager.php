<?php

namespace Evolvex\InvariantSentinel;

use Evolvex\InvariantSentinel\Contracts\FactStore;
use Evolvex\InvariantSentinel\Contracts\Invariant;
use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Contracts\TenantResolver;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\Evaluation\EvaluationEngine;
use Evolvex\InvariantSentinel\Queue\PendingCheckDispatcher;
use Evolvex\InvariantSentinel\Testing\SentinelFake;
use Evolvex\InvariantSentinel\ValueObjects\EvaluationResult;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Database\Eloquent\Model;

final class SentinelManager
{
    private ?SentinelFake $fake = null;

    public function __construct(
        private readonly InvariantRegistry $registry,
        private readonly EvaluationEngine $engine,
        private readonly PendingCheckDispatcher $dispatcher,
        private readonly FactStore $facts,
        private readonly TenantResolver $tenants,
    ) {}

    public function register(string|Invariant $invariant): self { $this->registry->register($invariant); return $this; }
    public function fake(): SentinelFake { return $this->fake ??= new SentinelFake(); }
    public function restore(): void { $this->fake = null; }

    public function trigger(string $invariantKey, SubjectRef|Model $subject, TriggerType $trigger = TriggerType::Manual): void
    {
        $ref = $this->normalize($subject);
        if ($this->fake) { $this->fake->record($invariantKey, $ref); return; }
        $this->dispatcher->schedule($invariantKey, $ref, $trigger);
    }

    public function check(string $invariantKey, SubjectRef|Model $subject): EvaluationResult
    {
        return $this->engine->evaluate($invariantKey, $this->normalize($subject), TriggerType::Manual);
    }

    public function assertHealthy(string $invariantKey, SubjectRef|Model $subject): EvaluationResult
    {
        $result = $this->check($invariantKey, $subject);
        if (! $result->isHealthy()) throw new \RuntimeException("Invariant [{$invariantKey}] is [{$result->status->value}], not healthy.");
        return $result;
    }

    public function assertViolation(string $invariantKey, SubjectRef|Model $subject, ?string $ruleKey = null): EvaluationResult
    {
        $result = $this->check($invariantKey, $subject);
        if ($result->status !== \Evolvex\InvariantSentinel\Enums\EvaluationStatus::Fail) {
            throw new \RuntimeException("Invariant [{$invariantKey}] did not fail; status was [{$result->status->value}].");
        }
        if ($ruleKey !== null && ! in_array($ruleKey, $result->failedRuleKeys(), true)) {
            throw new \RuntimeException("Invariant [{$invariantKey}] failed, but rule [{$ruleKey}] did not.");
        }
        return $result;
    }

    public function subject(Model $model): SubjectRef { return $this->normalize($model); }
    public function facts(): FactStore { return $this->facts; }
    public function registry(): InvariantRegistry { return $this->registry; }

    private function normalize(SubjectRef|Model $subject): SubjectRef
    {
        return $subject instanceof SubjectRef ? $subject : SubjectRef::fromModel($subject, $this->tenants->currentTenantKey());
    }
}
