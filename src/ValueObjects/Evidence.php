<?php

namespace Evolvex\InvariantSentinel\ValueObjects;

use DateTimeInterface;

final readonly class Evidence
{
    public function __construct(
        public string $type,
        public string $source,
        public mixed $expected = null,
        public mixed $actual = null,
        public array $metadata = [],
        public ?DateTimeInterface $observedAt = null,
    ) {}

    public static function database(string $source, mixed $expected = null, mixed $actual = null, array $metadata = []): self
    {
        return new self('database', $source, $expected, $actual, $metadata, now());
    }

    public static function external(string $source, mixed $expected = null, mixed $actual = null, array $metadata = []): self
    {
        return new self('external', $source, $expected, $actual, $metadata, now());
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'source' => $this->source,
            'expected' => $this->expected,
            'actual' => $this->actual,
            'metadata' => $this->metadata,
            'observed_at' => ($this->observedAt ?? now())->format(DATE_ATOM),
        ];
    }
}
