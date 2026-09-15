<?php

namespace Evolvex\InvariantSentinel\ValueObjects;

use Illuminate\Database\Eloquent\Model;

final readonly class SubjectRef
{
    public function __construct(
        public string $type,
        public string $id,
        public string $tenantKey = '__global__',
    ) {}

    public static function make(string $type, string|int $id, ?string $tenantKey = null): self
    {
        return new self($type, (string) $id, $tenantKey ?: '__global__');
    }

    public static function fromModel(Model $model, ?string $tenantKey = null): self
    {
        return new self($model::class, (string) $model->getKey(), $tenantKey ?: '__global__');
    }

    public function lockKey(string $invariantKey): string
    {
        return hash('sha256', implode('|', [$invariantKey, $this->tenantKey, $this->type, $this->id]));
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'tenant_key' => $this->tenantKey,
        ];
    }
}
