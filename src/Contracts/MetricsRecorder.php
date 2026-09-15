<?php

namespace Evolvex\InvariantSentinel\Contracts;

interface MetricsRecorder
{
    public function increment(string $metric, array $tags = [], int $value = 1): void;
    public function timing(string $metric, int $milliseconds, array $tags = []): void;
}
