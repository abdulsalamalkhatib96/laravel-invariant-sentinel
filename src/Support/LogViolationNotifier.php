<?php

namespace Evolvex\InvariantSentinel\Support;

use Evolvex\InvariantSentinel\Contracts\ViolationNotifier;
use Evolvex\InvariantSentinel\Models\Incident;
use Illuminate\Support\Facades\Log;

final class LogViolationNotifier implements ViolationNotifier
{
    public function opened(Incident $incident): void
    {
        Log::critical('Sentinel invariant incident opened.', $this->context($incident));
    }
    public function resolved(Incident $incident): void
    {
        Log::info('Sentinel invariant incident resolved.', $this->context($incident));
    }
    private function context(Incident $incident): array
    {
        return [
            'incident_id' => $incident->id,
            'invariant' => $incident->invariant_key,
            'subject_type' => $incident->subject_type,
            'subject_id' => $incident->subject_id,
            'severity' => $incident->severity,
            'rule_keys' => $incident->rule_keys,
        ];
    }
}
