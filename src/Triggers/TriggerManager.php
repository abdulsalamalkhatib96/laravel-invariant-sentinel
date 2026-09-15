<?php

namespace Evolvex\InvariantSentinel\Triggers;

use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Contracts\TenantResolver;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\Queue\PendingCheckDispatcher;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Throwable;

final class TriggerManager
{
    private bool $booted = false;

    public function __construct(
        private readonly InvariantRegistry $registry,
        private readonly PendingCheckDispatcher $dispatcher,
        private readonly TenantResolver $tenants,
    ) {}

    public function boot(): void
    {
        if ($this->booted) return;
        $this->booted = true;
        foreach ($this->registry->all() as $invariant) {
            foreach ($invariant->triggers() as $trigger) {
                if ($trigger instanceof EventTrigger) $this->registerEvent($invariant::key(), $trigger);
                if ($trigger instanceof ModelTrigger) $this->registerModel($invariant::key(), $trigger);
            }
        }
    }

    private function registerEvent(string $invariantKey, EventTrigger $trigger): void
    {
        Event::listen($trigger->eventClass, function ($event) use ($invariantKey, $trigger) {
            $ref = ($trigger->subjectResolver)($event);
            $this->afterCommit(fn () => $this->dispatcher->schedule($invariantKey, $this->normalize($ref), TriggerType::Event));
        });
    }

    private function registerModel(string $invariantKey, ModelTrigger $trigger): void
    {
        $class = $trigger->modelClass;
        foreach ($trigger->events as $event) {
            if (! method_exists($class, $event)) continue;
            $class::$event(function (Model $model) use ($invariantKey, $trigger) {
                if ($trigger->when && ! ($trigger->when)($model)) return;
                $this->afterCommit(fn () => $this->dispatcher->schedule($invariantKey, SubjectRef::fromModel($model, $this->tenants->currentTenantKey()), TriggerType::Model));
            });
        }
    }

    private function normalize(mixed $value): SubjectRef
    {
        if ($value instanceof SubjectRef) return $value;
        if ($value instanceof Model) return SubjectRef::fromModel($value, $this->tenants->currentTenantKey());
        throw new \InvalidArgumentException('Trigger subject resolver must return SubjectRef or Eloquent model.');
    }

    private function afterCommit(callable $callback): void
    {
        DB::afterCommit(function () use ($callback) {
            try {
                $callback();
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
