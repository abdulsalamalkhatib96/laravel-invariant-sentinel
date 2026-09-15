<?php

namespace Evolvex\InvariantSentinel\Console;

use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class DoctorCommand extends Command
{
    protected $signature = 'sentinel:doctor';
    protected $description = 'Validate Sentinel production configuration';
    public function handle(InvariantRegistry $registry): int
    {
        $issues = [];
        foreach (['sentinel_states','sentinel_observations','sentinel_incidents','sentinel_pending_checks','sentinel_facts'] as $table) {
            if (! Schema::connection(config('sentinel.storage.connection'))->hasTable($table)) $issues[] = ['ERROR', "Missing table {$table}"];
        }
        if (config('queue.default') === 'sync') $issues[] = ['WARN', 'Queue connection is sync; production checks will run in-process.'];
        if (in_array(config('cache.default'), ['array', 'file'], true)) $issues[] = ['WARN', 'Cache store is not suitable for cross-node distributed locking.'];
        if (config('sentinel.dashboard.enabled', false)) {
            $middleware = (array) config('sentinel.dashboard.middleware', []);
            if (! in_array('auth', $middleware, true)) $issues[] = ['WARN', 'Dashboard is enabled without auth middleware.'];
            if (! collect($middleware)->contains(fn ($m) => is_string($m) && str_starts_with($m, 'can:'))) $issues[] = ['WARN', 'Dashboard is enabled without authorization middleware.'];
        }
        if (config('sentinel.remediation.automatic', false)) $issues[] = ['ERROR', 'Automatic remediation must not be enabled globally; keep repairs explicit and idempotent.'];
        if ($registry->all() === []) $issues[] = ['WARN', 'No invariants are registered.'];
        foreach ($registry->all() as $key => $invariant) {
            if ($invariant->checks() === []) $issues[] = ['WARN', "Invariant {$key} has no checks."];
        }
        if ($issues === []) { $this->info('Sentinel configuration looks healthy.'); return self::SUCCESS; }
        $this->table(['Level', 'Issue'], $issues);
        return collect($issues)->contains(fn ($x) => $x[0] === 'ERROR') ? self::FAILURE : self::SUCCESS;
    }
}
