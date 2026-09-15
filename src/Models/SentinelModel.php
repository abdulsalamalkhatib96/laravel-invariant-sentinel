<?php

namespace Evolvex\InvariantSentinel\Models;

use Illuminate\Database\Eloquent\Model;

abstract class SentinelModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function getConnectionName()
    {
        return config('sentinel.storage.connection') ?: parent::getConnectionName();
    }
}
