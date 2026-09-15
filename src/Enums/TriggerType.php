<?php

namespace Evolvex\InvariantSentinel\Enums;

enum TriggerType: string
{
    case Manual = 'manual';
    case Event = 'event';
    case Model = 'model';
    case Sweep = 'sweep';
    case Retry = 'retry';
    case Recheck = 'recheck';
}
