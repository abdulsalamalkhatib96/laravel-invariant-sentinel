<?php

namespace Evolvex\InvariantSentinel\Registry;

use Evolvex\InvariantSentinel\Contracts\Invariant;
use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use InvalidArgumentException;

final class InMemoryInvariantRegistry implements InvariantRegistry
{
    /** @var array<string, Invariant> */
    private array $invariants = [];

    public function __construct(private readonly \Illuminate\Contracts\Container\Container $container) {}

    public function register(string|Invariant $invariant): void
    {
        $instance = is_string($invariant) ? $this->container->make($invariant) : $invariant;
        $key = $instance::key();

        if (isset($this->invariants[$key]) && $this->invariants[$key]::class !== $instance::class) {
            throw new InvalidArgumentException("Duplicate invariant key [{$key}].");
        }

        $this->invariants[$key] = $instance;
    }

    public function get(string $key): Invariant
    {
        if (! isset($this->invariants[$key])) {
            throw new InvalidArgumentException("Invariant [{$key}] is not registered.");
        }
        return $this->invariants[$key];
    }

    public function has(string $key): bool { return isset($this->invariants[$key]); }
    public function all(): array { return $this->invariants; }
}
