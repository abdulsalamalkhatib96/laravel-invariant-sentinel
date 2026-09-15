<?php

namespace Evolvex\InvariantSentinel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class PendingCheck extends SentinelModel
{
    use HasUlids;
    protected $table = 'sentinel_pending_checks';
    protected $casts = [
        'not_before' => 'immutable_datetime',
        'first_triggered_at' => 'immutable_datetime',
        'last_triggered_at' => 'immutable_datetime',
        'claimed_at' => 'immutable_datetime',
    ];
}
