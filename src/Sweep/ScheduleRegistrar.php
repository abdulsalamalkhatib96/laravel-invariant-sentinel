<?php

namespace Evolvex\InvariantSentinel\Sweep;

use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Triggers\SweepTrigger;
use Illuminate\Console\Scheduling\Schedule;

final class ScheduleRegistrar
{
    public function __construct(private readonly InvariantRegistry $registry) {}

    public function register(Schedule $schedule): void
    {
        if (! config('sentinel.scheduler.enabled', false)) return;

        foreach ($this->registry->all() as $key => $invariant) {
            foreach ($invariant->triggers() as $trigger) {
                if (! $trigger instanceof SweepTrigger) continue;
                $schedule->command('sentinel:sweep', [$key])
                    ->cron($trigger->expression)
                    ->name('sentinel:sweep:'.$key)
                    ->onOneServer()
                    ->withoutOverlapping();
            }
        }

        $schedule->command('sentinel:drain')
            ->cron((string) config('sentinel.scheduler.drain_cron', '* * * * *'))
            ->name('sentinel:drain')
            ->onOneServer()
            ->withoutOverlapping();

        $schedule->command('sentinel:prune')
            ->cron((string) config('sentinel.scheduler.prune_cron', '17 3 * * *'))
            ->name('sentinel:prune')
            ->onOneServer()
            ->withoutOverlapping();
    }
}
