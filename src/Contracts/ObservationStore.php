<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Models\Observation;

interface ObservationStore
{
    public function persist(array $attributes): Observation;
    public function pruneBefore(\DateTimeInterface $before): int;
}
