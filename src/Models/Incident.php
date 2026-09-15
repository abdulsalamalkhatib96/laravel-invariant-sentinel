<?php

namespace Evolvex\InvariantSentinel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class Incident extends SentinelModel
{
    use HasUlids;
    protected $table = 'sentinel_incidents';
    protected $casts = [
        'rule_keys' => 'array',
        'meta' => 'array',
        'opened_at' => 'immutable_datetime',
        'acknowledged_at' => 'immutable_datetime',
        'resolved_at' => 'immutable_datetime',
    ];
}
