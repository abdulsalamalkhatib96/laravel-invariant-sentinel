<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Models\Incident;

interface ViolationNotifier
{
    public function opened(Incident $incident): void;
    public function resolved(Incident $incident): void;
}
