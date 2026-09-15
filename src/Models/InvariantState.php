<?php

namespace Evolvex\InvariantSentinel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class InvariantState extends SentinelModel
{
    use HasUlids;
    protected $table = 'sentinel_states';
    protected $casts = [
        'first_failed_at' => 'immutable_datetime',
        'last_failed_at' => 'immutable_datetime',
        'last_evaluated_at' => 'immutable_datetime',
        'next_evaluation_at' => 'immutable_datetime',
        'meta' => 'array',
    ];
}
