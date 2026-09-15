<?php

namespace Evolvex\InvariantSentinel\Sweep;

use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Contracts\SweepableInvariant;
use Evolvex\InvariantSentinel\Contracts\ViolationFindingInvariant;
use Evolvex\InvariantSentinel\Enums\TriggerType;
use Evolvex\InvariantSentinel\Queue\PendingCheckDispatcher;

final class SweepEngine
{
    public function __construct(private readonly InvariantRegistry $registry, private readonly PendingCheckDispatcher $dispatcher) {}

    public function sweep(?string $only = null): array
    {
        $counts = [];
        $invariants = $only ? [$only => $this->registry->get($only)] : $this->registry->all();
        foreach ($invariants as $key => $invariant) {
            $count = 0;
            $subjects = $invariant instanceof ViolationFindingInvariant
                ? $invariant->violations()
                : ($invariant instanceof SweepableInvariant ? $invariant->candidates() : []);
            foreach ($subjects as $subject) {
                $this->dispatcher->schedule($key, $subject, TriggerType::Sweep);
                $count++;
            }
            $counts[$key] = $count;
        }
        return $counts;
    }
}
