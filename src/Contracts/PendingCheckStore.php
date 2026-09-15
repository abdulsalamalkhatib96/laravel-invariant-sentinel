<?php

namespace Evolvex\InvariantSentinel\Contracts;

use DateTimeInterface;
use Evolvex\InvariantSentinel\Models\PendingCheck;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

interface PendingCheckStore
{
    public function schedule(string $invariantKey, SubjectRef $subject, DateTimeInterface $notBefore, string $trigger): PendingCheck;
    /** @return list<PendingCheck> */
    public function due(int $limit): array;
    public function claim(PendingCheck $check): bool;
    public function complete(PendingCheck $check): void;
    public function release(PendingCheck $check, DateTimeInterface $notBefore, ?string $error = null): void;
}
