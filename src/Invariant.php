<?php

namespace Evolvex\InvariantSentinel;

use Evolvex\InvariantSentinel\Consistency\ConsistencyPolicy;
use Evolvex\InvariantSentinel\Contracts\Invariant as InvariantContract;
use Evolvex\InvariantSentinel\Enums\Severity;
use Evolvex\InvariantSentinel\ValueObjects\IncidentPolicy;

abstract class Invariant implements InvariantContract
{
    public static function version(): int { return 1; }
    public function severity(): Severity { return Severity::High; }
    public function consistency(): ConsistencyPolicy { return ConsistencyPolicy::immediate(); }
    public function incidentPolicy(): IncidentPolicy { return IncidentPolicy::make(); }
    public function appliesTo(mixed $subject): bool { return $subject !== null; }
    public function triggers(): array { return []; }
}
