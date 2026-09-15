<?php

namespace Evolvex\InvariantSentinel\Enums;

enum EvaluationStatus: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Deferred = 'deferred';
    case Unknown = 'unknown';
    case Error = 'error';
    case NotApplicable = 'not_applicable';
}
