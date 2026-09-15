<?php

namespace Evolvex\InvariantSentinel\Support;

use Evolvex\InvariantSentinel\Contracts\MetricsRecorder;

final class NoopMetricsRecorder implements MetricsRecorder
{
    public function increment(string $metric, array $tags = [], int $value = 1): void {}
    public function timing(string $metric, int $milliseconds, array $tags = []): void {}
}
