<?php

namespace Evolvex\InvariantSentinel\Persistence;

use Evolvex\InvariantSentinel\Contracts\StateStore;
use Evolvex\InvariantSentinel\Models\InvariantState;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

final class DatabaseStateStore implements StateStore
{
    public function get(string $invariantKey, SubjectRef $subject): ?InvariantState
    {
        return InvariantState::query()
            ->where('invariant_key', $invariantKey)
            ->where('tenant_key', $subject->tenantKey)
            ->where('subject_type', $subject->type)
            ->where('subject_id', $subject->id)
            ->first();
    }

    public function persist(InvariantState $state): InvariantState
    {
        $state->save();
        return $state;
    }
}
