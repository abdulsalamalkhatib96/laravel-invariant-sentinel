<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Consistency\ConsistencyPolicy;
use Evolvex\InvariantSentinel\Enums\Severity;
use Evolvex\InvariantSentinel\ValueObjects\IncidentPolicy;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

interface Invariant
{
    public static function key(): string;
    public static function version(): int;
    public function severity(): Severity;
    public function consistency(): ConsistencyPolicy;
    public function incidentPolicy(): IncidentPolicy;
    /** @return list<InvariantCheck> */
    public function checks(): array;
    public function appliesTo(mixed $subject): bool;
    public function resolveSubject(SubjectRef $subject): mixed;
    /** @return array<int, object> */
    public function triggers(): array;
}
