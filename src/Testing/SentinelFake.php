<?php

namespace Evolvex\InvariantSentinel\Testing;

use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

final class SentinelFake
{
    /** @var list<array{invariant:string,subject:SubjectRef}> */
    public array $triggered = [];

    public function record(string $invariant, SubjectRef $subject): void { $this->triggered[] = compact('invariant', 'subject'); }

    public function assertTriggered(string $invariant, string|int|null $subjectId = null): void
    {
        $matched = array_filter($this->triggered, fn ($x) => $x['invariant'] === $invariant && ($subjectId === null || $x['subject']->id === (string) $subjectId));
        if ($matched === []) throw new \RuntimeException("Failed asserting invariant [{$invariant}] was triggered.");
    }

    public function assertNothingTriggered(): void
    {
        if ($this->triggered !== []) throw new \RuntimeException('Failed asserting no Sentinel invariants were triggered.');
    }
}
