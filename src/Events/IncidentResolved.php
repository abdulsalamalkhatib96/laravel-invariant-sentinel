<?php

namespace Evolvex\InvariantSentinel\Events;

final readonly class IncidentResolved
{
    public function __construct(public mixed $payload) {}
}
