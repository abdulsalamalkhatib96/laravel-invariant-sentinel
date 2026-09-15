<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Traversable;

interface SweepableInvariant
{
    /** @return iterable<\Evolvex\InvariantSentinel\ValueObjects\SubjectRef> */
    public function candidates(): iterable;
}
