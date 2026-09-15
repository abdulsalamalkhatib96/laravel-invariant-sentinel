<?php

namespace Evolvex\InvariantSentinel\Events;

final readonly class IncidentOpened
{
    public function __construct(public mixed $payload) {}
}
