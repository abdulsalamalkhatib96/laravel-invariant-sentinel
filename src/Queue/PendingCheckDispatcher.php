<?php

namespace Evolvex\InvariantSentinel\Queue;

use Evolvex\InvariantSentinel\Contracts\PendingCheckStore;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Throwable;

final class PendingCheckDispatcher
{
    public function __construct(private readonly PendingCheckStore $store, private readonly BusDispatcher $bus) {}

    public function schedule(string $invariantKey, SubjectRef $subject, TriggerType $trigger, ?\DateTimeInterface $notBefore = null): void
    {
        if (! config('sentinel.enabled', true)) return;
        $this->store->schedule($invariantKey, $subject, $notBefore ?: now(), $trigger->value);
        $job = new DrainPendingChecksJob();
        if ($connection = config('sentinel.queue.connection')) $job->onConnection($connection);
        if ($queue = config('sentinel.queue.queue')) $job->onQueue($queue);
        $job->afterCommit();
        try {
            $this->bus->dispatch($job);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
