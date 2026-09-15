<?php

namespace Evolvex\InvariantSentinel\Persistence;

use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Evolvex\InvariantSentinel\Models\Incident;

final class DatabaseIncidentStore implements IncidentStore
{
    public function find(string $id): ?Incident { return Incident::query()->find($id); }
    public function persist(Incident $incident): Incident { $incident->save(); return $incident; }
    public function open(): iterable { return Incident::query()->whereIn('status', ['open', 'acknowledged', 'resolving'])->latest('opened_at')->get(); }
}
