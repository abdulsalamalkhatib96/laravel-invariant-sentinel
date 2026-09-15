<?php

namespace Evolvex\InvariantSentinel\Triggers;

use Closure;

final readonly class EventTrigger
{
    public function __construct(public string $eventClass, public Closure $subjectResolver) {}
    public static function make(string $eventClass, Closure $subjectResolver): self { return new self($eventClass, $subjectResolver); }
}
