<?php

namespace Evolvex\InvariantSentinel\Contracts;

use Evolvex\InvariantSentinel\Evaluation\EvaluationContext;
use Evolvex\InvariantSentinel\ValueObjects\CheckResult;

interface InvariantCheck
{
    public function key(): string;
    public function evaluate(mixed $subject, EvaluationContext $context): CheckResult;
}
