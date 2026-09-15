<?php

namespace Evolvex\InvariantSentinel\Incidents;

use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Evolvex\InvariantSentinel\Enums\IncidentStatus;
use Evolvex\InvariantSentinel\Models\AuditLog;
use Evolvex\InvariantSentinel\Models\Incident;

final class IncidentService
{
    public function __construct(private readonly IncidentStore $store) {}

    public function acknowledge(string $id, ?string $actor = null): Incident
    {
        $incident = $this->required($id);
        $before = $incident->toArray();
        $incident->status = IncidentStatus::Acknowledged->value;
        $incident->acknowledged_at = now();
        $incident->acknowledged_by = $actor;
        $this->store->persist($incident);
        $this->audit('incident.acknowledged', $incident, $before, $incident->toArray(), $actor);
        return $incident;
    }

    public function resolve(string $id, string $reason = 'manual', ?string $actor = null): Incident
    {
        $incident = $this->required($id);
        $before = $incident->toArray();
        $incident->status = IncidentStatus::Resolved->value;
        $incident->resolved_at = now();
        $incident->resolution_reason = $reason;
        $this->store->persist($incident);
        $this->audit('incident.resolved', $incident, $before, $incident->toArray(), $actor);
        return $incident;
    }

    private function required(string $id): Incident
    {
        return $this->store->find($id) ?? throw new \InvalidArgumentException("Incident [{$id}] not found.");
    }

    private function audit(string $action, Incident $incident, array $before, array $after, ?string $actor): void
    {
        AuditLog::query()->create([
            'action' => $action,
            'actor' => $actor,
            'incident_id' => $incident->id,
            'before' => $before,
            'after' => $after,
            'context' => ['ip' => app()->bound('request') ? request()->ip() : null],
        ]);
    }
}
