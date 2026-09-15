<?php

namespace Evolvex\InvariantSentinel\Facades;

use Illuminate\Support\Facades\Facade;

final class Sentinel extends Facade
{
    protected static function getFacadeAccessor(): string { return \Evolvex\InvariantSentinel\SentinelManager::class; }
}
