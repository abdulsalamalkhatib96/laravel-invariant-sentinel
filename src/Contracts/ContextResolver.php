<?php

namespace Evolvex\InvariantSentinel\Contracts;

interface ContextResolver
{
    public function capture(): array;
}
