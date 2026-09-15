<?php

namespace Evolvex\InvariantSentinel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class Observation extends SentinelModel
{
    use HasUlids;
    protected $table = 'sentinel_observations';
    protected $casts = [
        'checks' => 'array',
        'context' => 'array',
        'started_at' => 'immutable_datetime',
        'finished_at' => 'immutable_datetime',
    ];
}
