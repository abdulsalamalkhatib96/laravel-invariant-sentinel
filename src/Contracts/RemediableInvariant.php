<?php

namespace Evolvex\InvariantSentinel\Contracts;

interface RemediableInvariant
{
    public function remediation(): Remediation;
}
