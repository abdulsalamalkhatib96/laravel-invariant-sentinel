<?php

namespace Evolvex\InvariantSentinel\Support;

use Evolvex\InvariantSentinel\Contracts\TenantResolver;

final class DefaultTenantResolver implements TenantResolver
{
    public function currentTenantKey(): string { return '__global__'; }
}
