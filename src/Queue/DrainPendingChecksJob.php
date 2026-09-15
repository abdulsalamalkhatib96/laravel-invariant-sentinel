<?php

namespace Evolvex\InvariantSentinel\Queue;

use Evolvex\InvariantSentinel\Contracts\PendingCheckStore;
use Evolvex\InvariantSentinel\Enums\EvaluationStatus;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\Evaluation\EvaluationEngine;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class DrainPendingChecksJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function handle(PendingCheckStore $store, EvaluationEngine $engine): void
    {
        $limit = (int) config('sentinel.queue.drain_batch', 100);
        foreach ($store->due($limit) as $check) {
            if (! $store->claim($check)) continue;
            try {
                $result = $engine->evaluate(
                    $check->invariant_key,
                    SubjectRef::make($check->subject_type, $check->subject_id, $check->tenant_key),
                    TriggerType::tryFrom($check->last_trigger) ?? TriggerType::Retry,
                );
                if ($result->status === EvaluationStatus::Deferred && $result->nextCheckAt) {
                    $store->release($check, $result->nextCheckAt);
                } else {
                    $store->complete($check);
                }
            } catch (Throwable $e) {
                $store->release($check, now()->addSeconds(min(300, 5 * max(1, (int) $check->attempts))), $e->getMessage());
                report($e);
            }
        }
    }
}
