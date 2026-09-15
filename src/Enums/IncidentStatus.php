<?php

namespace Evolvex\InvariantSentinel\Enums;

enum IncidentStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolving = 'resolving';
    case Resolved = 'resolved';
}
