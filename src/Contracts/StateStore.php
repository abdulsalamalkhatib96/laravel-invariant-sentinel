<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Models\InvariantState;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

interface StateStore
{
    public function get(string $invariantKey, SubjectRef $subject): ?InvariantState;
    public function persist(InvariantState $state): InvariantState;
}
