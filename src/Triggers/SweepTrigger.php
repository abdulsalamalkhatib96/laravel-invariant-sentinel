<?php

namespace Evolvex\InvariantSentinel\Triggers;

final readonly class SweepTrigger
{
    public function __construct(public string $expression = '* * * * *') {}
}
