<?php

namespace Evolvex\InvariantSentinel\Persistence;

use Evolvex\InvariantSentinel\Contracts\ObservationStore;
use Evolvex\InvariantSentinel\Models\Observation;

final class DatabaseObservationStore implements ObservationStore
{
    public function persist(array $attributes): Observation
    {
        return Observation::query()->create($attributes);
    }

    public function pruneBefore(\DateTimeInterface $before): int
    {
        return Observation::query()->where('created_at', '<', $before)->delete();
    }
}
