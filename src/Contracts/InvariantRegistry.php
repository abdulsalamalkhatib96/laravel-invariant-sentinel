<?php

namespace Evolvex\InvariantSentinel\Contracts;

interface InvariantRegistry
{
    /** @param class-string<Invariant>|Invariant $invariant */
    public function register(string|Invariant $invariant): void;
    public function get(string $key): Invariant;
    public function has(string $key): bool;
    /** @return array<string, Invariant> */
    public function all(): array;
}
