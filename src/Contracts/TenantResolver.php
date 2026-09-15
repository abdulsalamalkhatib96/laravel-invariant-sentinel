<?php

namespace Evolvex\InvariantSentinel\Contracts;

interface TenantResolver
{
    public function currentTenantKey(): string;
}
