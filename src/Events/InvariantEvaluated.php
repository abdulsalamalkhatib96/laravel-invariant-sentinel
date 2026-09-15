<?php

namespace Evolvex\InvariantSentinel\Events;

final readonly class InvariantEvaluated
{
    public function __construct(public mixed $payload) {}
}
