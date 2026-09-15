<?php

namespace Evolvex\InvariantSentinel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class Fact extends SentinelModel
{
    use HasUlids;
    protected $table = 'sentinel_facts';
    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'immutable_datetime',
        'recorded_at' => 'immutable_datetime',
    ];
}
