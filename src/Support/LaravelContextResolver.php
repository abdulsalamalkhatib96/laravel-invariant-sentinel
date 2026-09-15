<?php

namespace Evolvex\InvariantSentinel\Support;

use Evolvex\InvariantSentinel\Contracts\ContextResolver;
use Illuminate\Support\Str;

final class LaravelContextResolver implements ContextResolver
{
    public function capture(): array
    {
        $context = [];
        $keys = ['correlation_id', 'trace_id', 'request_id', 'actor_id', 'tenant_id'];
        if (class_exists(\Illuminate\Support\Facades\Context::class)) {
            foreach ($keys as $key) {
                $value = \Illuminate\Support\Facades\Context::get($key);
                if ($value !== null) $context[$key] = $value;
            }
        }
        $context['sentinel_evaluation_id'] = (string) Str::ulid();
        return $context;
    }
}
