<?php

namespace Evolvex\InvariantSentinel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class AuditLog extends SentinelModel
{
    use HasUlids;
    protected $table = 'sentinel_audit_logs';
    protected $casts = ['context' => 'array', 'before' => 'array', 'after' => 'array'];
}
