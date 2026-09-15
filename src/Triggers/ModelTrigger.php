<?php

namespace Evolvex\InvariantSentinel\Triggers;

use Closure;

final readonly class ModelTrigger
{
    /** @param list<string> $events */
    public function __construct(public string $modelClass, public array $events, public ?Closure $when = null) {}
    public static function make(string $modelClass, array $events = ['updated'], ?Closure $when = null): self { return new self($modelClass, $events, $when); }
}
