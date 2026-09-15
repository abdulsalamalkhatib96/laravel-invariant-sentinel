<?php

namespace Evolvex\InvariantSentinel\Testing;

use Evolvex\InvariantSentinel\Contracts\Invariant;
use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;
use Evolvex\InvariantSentinel\Evaluation\EvaluationEngine;
use Evolvex\InvariantSentinel\ValueObjects\EvaluationResult;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Database\Eloquent\Model;

final class InvariantTest
{
    private SubjectRef|Model|null $subject = null;
    private string $key;

    /** @param class-string<Invariant>|string $invariant */
    private function __construct(string $invariant)
    {
        if (class_exists($invariant) && is_subclass_of($invariant, Invariant::class)) {
            app(InvariantRegistry::class)->register($invariant);
            $this->key = $invariant::key();
        } else {
            $this->key = $invariant;
        }
    }

    public static function for(string $invariant): self { return new self($invariant); }
    public function subject(SubjectRef|Model $subject): self { $this->subject = $subject; return $this; }

    public function evaluate(): EvaluationResult
    {
        if ($this->subject === null) throw new \LogicException('InvariantTest subject is required.');
        $ref = $this->subject instanceof SubjectRef ? $this->subject : app(\Evolvex\InvariantSentinel\SentinelManager::class)->subject($this->subject);
        return app(EvaluationEngine::class)->evaluate($this->key, $ref);
    }

    public function assertPasses(): EvaluationResult
    {
        $r = $this->evaluate();
        if ($r->status !== EvaluationStatus::Pass) throw new \RuntimeException("Expected PASS, got [{$r->status->value}].");
        return $r;
    }

    public function assertFails(?string $ruleKey = null): EvaluationResult
    {
        $r = $this->evaluate();
        if ($r->status !== EvaluationStatus::Fail) throw new \RuntimeException("Expected FAIL, got [{$r->status->value}].");
        if ($ruleKey !== null && ! in_array($ruleKey, $r->failedRuleKeys(), true)) throw new \RuntimeException("Expected failed rule [{$ruleKey}].");
        return $r;
    }
}
