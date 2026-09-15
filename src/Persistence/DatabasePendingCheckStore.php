<?php

namespace Evolvex\InvariantSentinel\Persistence;

use DateTimeInterface;
use Evolvex\InvariantSentinel\Contracts\PendingCheckStore;
use Evolvex\InvariantSentinel\Models\PendingCheck;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Database\QueryException;

final class DatabasePendingCheckStore implements PendingCheckStore
{
    public function schedule(string $invariantKey, SubjectRef $subject, DateTimeInterface $notBefore, string $trigger): PendingCheck
    {
        $key = [
            'invariant_key' => $invariantKey,
            'tenant_key' => $subject->tenantKey,
            'subject_type' => $subject->type,
            'subject_id' => $subject->id,
        ];

        $existing = PendingCheck::query()->where($key)->first();
        if ($existing) {
            $existing->forceFill([
                'status' => 'pending',
                'not_before' => ($existing->not_before && $existing->not_before->lte($notBefore)) ? $existing->not_before : $notBefore,
                'last_triggered_at' => now(),
                'trigger_count' => ((int) $existing->trigger_count) + 1,
                'last_trigger' => $trigger,
                'claimed_at' => null,
            ])->save();
            return $existing;
        }

        try {
            return PendingCheck::query()->create($key + [
                'status' => 'pending',
                'not_before' => $notBefore,
                'first_triggered_at' => now(),
                'last_triggered_at' => now(),
                'trigger_count' => 1,
                'attempts' => 0,
                'last_trigger' => $trigger,
            ]);
        } catch (QueryException) {
            $existing = PendingCheck::query()->where($key)->firstOrFail();
            $existing->forceFill([
                'status' => 'pending',
                'not_before' => ($existing->not_before && $existing->not_before->lte($notBefore)) ? $existing->not_before : $notBefore,
                'last_triggered_at' => now(),
                'trigger_count' => ((int) $existing->trigger_count) + 1,
                'last_trigger' => $trigger,
                'claimed_at' => null,
            ])->save();
            return $existing;
        }
    }

    public function due(int $limit): array
    {
        return PendingCheck::query()
            ->where('status', 'pending')
            ->where('not_before', '<=', now())
            ->orderBy('not_before')
            ->limit($limit)
            ->get()->all();
    }

    public function claim(PendingCheck $check): bool
    {
        return PendingCheck::query()
            ->whereKey($check->getKey())
            ->where('status', 'pending')
            ->where('trigger_count', $check->trigger_count)
            ->update([
                'status' => 'processing',
                'claimed_at' => now(),
                'attempts' => ((int) $check->attempts) + 1,
                'updated_at' => now(),
            ]) === 1;
    }

    public function complete(PendingCheck $check): void
    {
        PendingCheck::query()->whereKey($check->getKey())
            ->where('status', 'processing')
            ->where('trigger_count', $check->trigger_count)
            ->delete();
    }

    public function release(PendingCheck $check, DateTimeInterface $notBefore, ?string $error = null): void
    {
        PendingCheck::query()->whereKey($check->getKey())
            ->where('status', 'processing')
            ->where('trigger_count', $check->trigger_count)
            ->update([
            'status' => 'pending',
            'not_before' => $notBefore,
            'claimed_at' => null,
            'last_error' => $error,
            'updated_at' => now(),
        ]);
    }
}
