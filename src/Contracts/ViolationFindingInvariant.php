<?php

namespace Evolvex\InvariantSentinel\Contracts;

interface ViolationFindingInvariant
{
    /** @return iterable<\Evolvex\InvariantSentinel\ValueObjects\SubjectRef> */
    public function violations(): iterable;
}
