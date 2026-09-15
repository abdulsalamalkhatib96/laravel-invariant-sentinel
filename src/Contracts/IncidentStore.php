<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Enums\IncidentStatus;
use Evolvex\InvariantSentinel\Models\Incident;

interface IncidentStore
{
    public function find(string $id): ?Incident;
    public function persist(Incident $incident): Incident;
    /** @return iterable<Incident> */
    public function open(): iterable;
}
