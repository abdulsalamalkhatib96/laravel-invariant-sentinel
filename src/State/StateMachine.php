<?php

namespace Evolvex\InvariantSentinel\State;

use Evolvex\InvariantSentinel\Contracts\Invariant;
use Evolvex\InvariantSentinel\Consistency\StableFor;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;
use Evolvex\InvariantSentinel\Models\InvariantState;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

final class StateMachine
{
    public function transition(?InvariantState $state, Invariant $invariant, SubjectRef $subject, EvaluationStatus $status, ?\DateTimeInterface $nextEvaluationAt = null): InvariantState
    {
        $state ??= new InvariantState([
            'invariant_key' => $invariant::key(),
            'invariant_version' => $invariant::version(),
            'tenant_key' => $subject->tenantKey,
            'subject_type' => $subject->type,
            'subject_id' => $subject->id,
            'consecutive_failures' => 0,
            'consecutive_passes' => 0,
        ]);

        $state->invariant_version = $invariant::version();
        $state->status = $status->value;
        $state->last_evaluated_at = now();
        $state->next_evaluation_at = $nextEvaluationAt;

        if ($status === EvaluationStatus::Deferred) {
            $state->first_failed_at ??= now();
            $state->last_failed_at = now();
            $state->consecutive_passes = 0;
        } elseif ($status === EvaluationStatus::Fail) {
            $state->first_failed_at ??= now();
            $state->last_failed_at = now();
            $state->consecutive_failures = ((int) $state->consecutive_failures) + 1;
            $state->consecutive_passes = 0;
        } elseif ($status === EvaluationStatus::Pass) {
            $state->consecutive_passes = ((int) $state->consecutive_passes) + 1;
            $state->consecutive_failures = 0;
            $state->first_failed_at = null;
        } elseif ($invariant->consistency() instanceof StableFor) {
            // Unknown/error breaks the proof of continuous failure required by StableFor.
            $state->first_failed_at = null;
            $state->consecutive_failures = 0;
            $state->consecutive_passes = 0;
        }

        return $state;
    }
}
