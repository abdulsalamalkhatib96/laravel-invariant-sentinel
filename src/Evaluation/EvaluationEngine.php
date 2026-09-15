<?php

namespace Evolvex\InvariantSentinel\Evaluation;

use Evolvex\InvariantSentinel\Contracts\ContextResolver;
use Evolvex\InvariantSentinel\Contracts\EvidenceSanitizer;
use Evolvex\InvariantSentinel\Contracts\FactStore;
use Evolvex\InvariantSentinel\Contracts\Invariant;
use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Contracts\MetricsRecorder;
use Evolvex\InvariantSentinel\Contracts\ObservationStore;
use Evolvex\InvariantSentinel\Contracts\StateStore;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\Incidents\IncidentManager;
use Evolvex\InvariantSentinel\Exceptions\EvaluationBudgetExceeded;
use Evolvex\InvariantSentinel\Events\InvariantEvaluated;
use Evolvex\InvariantSentinel\State\StateMachine;
use Evolvex\InvariantSentinel\ValueObjects\CheckResult;
use Evolvex\InvariantSentinel\ValueObjects\EvaluationResult;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Throwable;

final class EvaluationEngine
{
    public function __construct(
        private readonly InvariantRegistry $registry,
        private readonly StateStore $states,
        private readonly ObservationStore $observations,
        private readonly FactStore $facts,
        private readonly ContextResolver $contextResolver,
        private readonly EvidenceSanitizer $sanitizer,
        private readonly StateMachine $stateMachine,
        private readonly IncidentManager $incidents,
        private readonly MetricsRecorder $metrics,
    ) {}

    public function evaluate(string|Invariant $invariant, SubjectRef $subject, TriggerType $trigger = TriggerType::Manual): EvaluationResult
    {
        $instance = is_string($invariant) ? $this->registry->get($invariant) : $invariant;
        $lockStore = config('sentinel.locks.store');
        $cache = $lockStore ? Cache::store($lockStore) : Cache::store();
        $lock = $cache->lock('sentinel:evaluate:'.$subject->lockKey($instance::key()), (int) config('sentinel.locks.seconds', 30));

        try {
            return $lock->block((int) config('sentinel.locks.wait_seconds', 2), fn () => $this->evaluateUnlocked($instance, $subject, $trigger));
        } catch (LockTimeoutException $e) {
            throw $e;
        }
    }

    private function evaluateUnlocked(Invariant $invariant, SubjectRef $subject, TriggerType $trigger): EvaluationResult
    {
        $started = microtime(true);
        $startedAt = now();
        $context = $this->contextResolver->capture();
        $evaluationId = $context['sentinel_evaluation_id'] ?? (string) Str::ulid();
        $existingState = $this->states->get($invariant::key(), $subject);

        $resolved = $invariant->resolveSubject($subject);
        if ($resolved === null || ! $invariant->appliesTo($resolved)) {
            return $this->finish($invariant, $subject, $trigger, $evaluationId, $started, $startedAt, $context, [], EvaluationStatus::NotApplicable, $existingState);
        }

        $budget = new EvaluationBudget(
            (int) config('sentinel.evaluation.max_queries', 50),
            (int) config('sentinel.evaluation.max_duration_ms', 3000),
            (int) config('sentinel.evaluation.max_external_calls', 0),
        );
        $evaluationContext = new EvaluationContext($subject, $context, $budget, $this->facts);
        $checks = [];
        $connection = DB::connection();
        $wasLogging = method_exists($connection, 'logging') ? $connection->logging() : false;
        if (! $wasLogging) {
            $connection->flushQueryLog();
            $connection->enableQueryLog();
        }

        try {
            foreach ($invariant->checks() as $check) {
                try {
                    $result = $check->evaluate($resolved, $evaluationContext);
                    if ($result->ruleKey !== $check->key()) {
                        $result = new CheckResult($check->key(), $result->status, $result->code, $result->message, $result->expected, $result->actual, $result->evidence, $result->meta);
                    }
                    $checks[] = $result;
                } catch (Throwable $e) {
                    $checks[] = CheckResult::error($check->key(), $e);
                }

                $queryCount = count($connection->getQueryLog());
                if ($queryCount > $budget->maxQueries) {
                    $checks[] = CheckResult::error('sentinel.evaluation-budget', new EvaluationBudgetExceeded("Query budget exceeded: {$queryCount} > {$budget->maxQueries}."));
                    break;
                }
                $elapsedMs = (int) round((microtime(true) - $started) * 1000);
                if ($elapsedMs > $budget->maxDurationMs) {
                    $checks[] = CheckResult::error('sentinel.evaluation-budget', new EvaluationBudgetExceeded("Duration budget exceeded: {$elapsedMs}ms > {$budget->maxDurationMs}ms."));
                    break;
                }
            }
        } finally {
            if (! $wasLogging) {
                $connection->disableQueryLog();
                $connection->flushQueryLog();
            }
        }

        $raw = $this->aggregate($checks);
        $firstFailureAt = $existingState?->first_failed_at;
        if ($raw === EvaluationStatus::Fail && $firstFailureAt === null) $firstFailureAt = now();
        $final = $invariant->consistency()->classify($raw, $firstFailureAt, now());

        return $this->finish($invariant, $subject, $trigger, $evaluationId, $started, $startedAt, $context, $checks, $final, $existingState);
    }

    private function aggregate(array $checks): EvaluationStatus
    {
        if ($checks === []) return EvaluationStatus::Pass;
        foreach ($checks as $r) if ($r->status === EvaluationStatus::Error) return EvaluationStatus::Error;
        foreach ($checks as $r) if ($r->status === EvaluationStatus::Fail) return EvaluationStatus::Fail;
        foreach ($checks as $r) if ($r->status === EvaluationStatus::Unknown) return EvaluationStatus::Unknown;
        return EvaluationStatus::Pass;
    }

    private function finish(Invariant $invariant, SubjectRef $subject, TriggerType $trigger, string $evaluationId, float $started, $startedAt, array $context, array $checks, EvaluationStatus $status, $existingState): EvaluationResult
    {
        $duration = (int) round((microtime(true) - $started) * 1000);
        $nextCheckAt = null;
        if ($status === EvaluationStatus::Deferred && ($retry = $invariant->consistency()->retryAfterSeconds())) {
            $nextCheckAt = now()->addSeconds($retry);
        }

        $serializedChecks = array_map(function (CheckResult $r) {
            $data = $r->toArray();
            $storeEvidence = $r->status === EvaluationStatus::Pass
                ? (bool) config('sentinel.evidence.store_passes', false)
                : (bool) config('sentinel.evidence.store_failures', true);
            if (! $storeEvidence) $data['evidence'] = [];
            return $data;
        }, $checks);
        $safeChecks = $this->sanitizer->sanitize($serializedChecks);
        $observation = $this->observations->persist([
            'evaluation_id' => $evaluationId,
            'invariant_key' => $invariant::key(),
            'invariant_version' => $invariant::version(),
            'tenant_key' => $subject->tenantKey,
            'subject_type' => $subject->type,
            'subject_id' => $subject->id,
            'status' => $status->value,
            'trigger' => $trigger->value,
            'duration_ms' => $duration,
            'checks' => $safeChecks,
            'context' => $this->sanitizer->sanitize($context),
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);

        $state = $this->stateMachine->transition($existingState, $invariant, $subject, $status, $nextCheckAt);
        $this->states->persist($state);
        $incident = $this->incidents->reconcile($invariant, $subject, $state, $status, $checks, $observation->id);
        $state->active_incident_id = $incident?->id;
        $this->states->persist($state);

        $tags = ['invariant' => $invariant::key(), 'status' => $status->value, 'severity' => $invariant->severity()->value];
        $this->metrics->increment('sentinel_evaluations_total', $tags);
        $this->metrics->timing('sentinel_evaluation_duration_ms', $duration, $tags);

        $result = new EvaluationResult($evaluationId, $invariant::key(), $invariant::version(), $subject, $status, $checks, $duration, $incident?->id, $nextCheckAt, $context);
        Event::dispatch(new InvariantEvaluated($result));
        return $result;
    }
}
