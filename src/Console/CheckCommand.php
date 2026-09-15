<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Evaluation\EvaluationEngine;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Console\Command;

final class CheckCommand extends Command
{
    protected $signature = 'sentinel:check {invariant} {subject} {--type=} {--tenant=__global__}';
    protected $description = 'Evaluate one invariant subject immediately';
    public function handle(EvaluationEngine $engine): int
    {
        $key = $this->argument('invariant');
        $type = $this->option('type') ?: 'subject';
        $result = $engine->evaluate($key, SubjectRef::make($type, $this->argument('subject'), $this->option('tenant')));
        $this->table(['Field', 'Value'], [
            ['Evaluation', $result->evaluationId], ['Status', $result->status->value], ['Duration', $result->durationMs.' ms'], ['Incident', $result->incidentId ?: '-']
        ]);
        return $result->status->value === 'fail' ? self::FAILURE : self::SUCCESS;
    }
}
