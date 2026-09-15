<?php

namespace Evolvex\InvariantSentinel\Persistence;

use Evolvex\InvariantSentinel\Contracts\FactStore;
use Evolvex\InvariantSentinel\Models\Fact;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

final class DatabaseFactStore implements FactStore
{
    public function record(string $type, SubjectRef $subject, string $idempotencyKey, array $payload = [], ?\DateTimeInterface $occurredAt = null): Fact
    {
        return Fact::query()->firstOrCreate(
            ['type' => $type, 'tenant_key' => $subject->tenantKey, 'idempotency_key' => $idempotencyKey],
            [
                'tenant_key' => $subject->tenantKey,
                'subject_type' => $subject->type,
                'subject_id' => $subject->id,
                'payload' => $payload,
                'occurred_at' => $occurredAt ?: now(),
                'recorded_at' => now(),
            ]
        );
    }

    public function count(string $type, SubjectRef $subject): int
    {
        return Fact::query()->where('type', $type)
            ->where('tenant_key', $subject->tenantKey)
            ->where('subject_type', $subject->type)
            ->where('subject_id', $subject->id)
            ->count();
    }
}
