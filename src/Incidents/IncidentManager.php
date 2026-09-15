<?php

namespace Evolvex\InvariantSentinel\Incidents;

use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Evolvex\InvariantSentinel\Contracts\Invariant;
use Evolvex\InvariantSentinel\Contracts\ViolationNotifier;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;
use Evolvex\InvariantSentinel\Enums\IncidentStatus;
use Evolvex\InvariantSentinel\Events\IncidentOpened;
use Evolvex\InvariantSentinel\Events\IncidentResolved;
use Illuminate\Support\Facades\Event;
use Evolvex\InvariantSentinel\Models\Incident;
use Evolvex\InvariantSentinel\Models\InvariantState;
use Evolvex\InvariantSentinel\ValueObjects\CheckResult;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

final class IncidentManager
{
    /** @param iterable<ViolationNotifier> $notifiers */
    public function __construct(private readonly IncidentStore $store, private readonly iterable $notifiers) {}

    /** @param list<CheckResult> $checks */
    public function reconcile(Invariant $invariant, SubjectRef $subject, InvariantState $state, EvaluationStatus $status, array $checks, string $observationId): ?Incident
    {
        $policy = $invariant->incidentPolicy();
        $active = $state->active_incident_id ? $this->store->find($state->active_incident_id) : null;

        if ($status === EvaluationStatus::Fail && (int) $state->consecutive_failures >= $policy->openAfterFailures) {
            $failed = array_values(array_map(fn (CheckResult $r) => $r->ruleKey,
                array_filter($checks, fn (CheckResult $r) => $r->status === EvaluationStatus::Fail)));
            sort($failed);
            $fingerprint = hash('sha256', implode('|', [$invariant::key(), $subject->tenantKey, $subject->type, $subject->id, ...$failed]));

            if ($active && $active->status !== IncidentStatus::Resolved->value) {
                $active->latest_observation_id = $observationId;
                $active->occurrence_count = ((int) $active->occurrence_count) + 1;
                $active->rule_keys = $failed;
                $active->fingerprint = $fingerprint;
                return $this->store->persist($active);
            }

            $incident = new Incident([
                'invariant_key' => $invariant::key(),
                'invariant_version' => $invariant::version(),
                'tenant_key' => $subject->tenantKey,
                'subject_type' => $subject->type,
                'subject_id' => $subject->id,
                'severity' => $invariant->severity()->value,
                'rule_keys' => $failed,
                'fingerprint' => $fingerprint,
                'status' => IncidentStatus::Open->value,
                'opened_at' => now(),
                'first_observation_id' => $observationId,
                'latest_observation_id' => $observationId,
                'occurrence_count' => 1,
            ]);
            $this->store->persist($incident);
            foreach ($this->notifiers as $notifier) $notifier->opened($incident);
            Event::dispatch(new IncidentOpened($incident));
            return $incident;
        }

        if ($status === EvaluationStatus::Pass && $active && $active->status !== IncidentStatus::Resolved->value
            && (int) $state->consecutive_passes >= $policy->resolveAfterPasses) {
            $active->status = IncidentStatus::Resolved->value;
            $active->resolved_at = now();
            $active->resolution_reason = 'state_became_valid';
            $active->latest_observation_id = $observationId;
            $this->store->persist($active);
            foreach ($this->notifiers as $notifier) $notifier->resolved($active);
            Event::dispatch(new IncidentResolved($active));
            return null;
        }

        return $active;
    }
}
