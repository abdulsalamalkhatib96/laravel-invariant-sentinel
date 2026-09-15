<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Models\Fact;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

interface FactStore
{
    public function record(string $type, SubjectRef $subject, string $idempotencyKey, array $payload = [], ?\DateTimeInterface $occurredAt = null): Fact;
    public function count(string $type, SubjectRef $subject): int;
}
