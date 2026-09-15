<?php

namespace Evolvex\InvariantSentinel;

use Evolvex\InvariantSentinel\Console\CheckCommand;
use Evolvex\InvariantSentinel\Console\DoctorCommand;
use Evolvex\InvariantSentinel\Console\DrainCommand;
use Evolvex\InvariantSentinel\Console\IncidentActionCommand;
use Evolvex\InvariantSentinel\Console\IncidentsCommand;
use Evolvex\InvariantSentinel\Console\ListCommand;
use Evolvex\InvariantSentinel\Console\MakeInvariantCommand;
use Evolvex\InvariantSentinel\Console\PruneCommand;
use Evolvex\InvariantSentinel\Console\RecheckCommand;
use Evolvex\InvariantSentinel\Console\RepairCommand;
use Evolvex\InvariantSentinel\Console\ShowCommand;
use Evolvex\InvariantSentinel\Console\SweepCommand;
use Evolvex\InvariantSentinel\Contracts\ContextResolver;
use Evolvex\InvariantSentinel\Contracts\EvidenceSanitizer;
use Evolvex\InvariantSentinel\Contracts\FactStore;
use Evolvex\InvariantSentinel\Contracts\IncidentStore;
use Evolvex\InvariantSentinel\Contracts\InvariantRegistry;
use Evolvex\InvariantSentinel\Contracts\MetricsRecorder;
use Evolvex\InvariantSentinel\Contracts\ObservationStore;
use Evolvex\InvariantSentinel\Contracts\PendingCheckStore;
use Evolvex\InvariantSentinel\Contracts\StateStore;
use Evolvex\InvariantSentinel\Contracts\TenantResolver;
use Evolvex\InvariantSentinel\Contracts\ViolationNotifier;
use Evolvex\InvariantSentinel\Http\DashboardController;
use Evolvex\InvariantSentinel\Persistence\DatabaseFactStore;
use Evolvex\InvariantSentinel\Persistence\DatabaseIncidentStore;
use Evolvex\InvariantSentinel\Persistence\DatabaseObservationStore;
use Evolvex\InvariantSentinel\Persistence\DatabasePendingCheckStore;
use Evolvex\InvariantSentinel\Persistence\DatabaseStateStore;
use Evolvex\InvariantSentinel\Registry\InMemoryInvariantRegistry;
use Evolvex\InvariantSentinel\Support\DefaultTenantResolver;
use Evolvex\InvariantSentinel\Support\LaravelContextResolver;
use Evolvex\InvariantSentinel\Support\LogViolationNotifier;
use Evolvex\InvariantSentinel\Support\NoopMetricsRecorder;
use Evolvex\InvariantSentinel\Support\RecursiveEvidenceSanitizer;
use Evolvex\InvariantSentinel\Triggers\TriggerManager;
use Evolvex\InvariantSentinel\Sweep\ScheduleRegistrar;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SentinelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sentinel.php', 'sentinel');

        $this->app->singleton(InvariantRegistry::class, InMemoryInvariantRegistry::class);
        $this->app->singleton(StateStore::class, DatabaseStateStore::class);
        $this->app->singleton(ObservationStore::class, DatabaseObservationStore::class);
        $this->app->singleton(IncidentStore::class, DatabaseIncidentStore::class);
        $this->app->singleton(PendingCheckStore::class, DatabasePendingCheckStore::class);
        $this->app->singleton(FactStore::class, DatabaseFactStore::class);
        $this->app->singleton(TenantResolver::class, DefaultTenantResolver::class);
        $this->app->singleton(ContextResolver::class, LaravelContextResolver::class);
        $this->app->singleton(EvidenceSanitizer::class, RecursiveEvidenceSanitizer::class);
        $this->app->singleton(MetricsRecorder::class, NoopMetricsRecorder::class);
        $this->app->singleton(ViolationNotifier::class, LogViolationNotifier::class);

        $this->app->when(\Evolvex\InvariantSentinel\Incidents\IncidentManager::class)
            ->needs('$notifiers')->give(fn ($app) => config('sentinel.alerts.log', true) ? [$app->make(ViolationNotifier::class)] : []);

        $this->app->singleton(SentinelManager::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/sentinel.php' => config_path('sentinel.php')], 'sentinel-config');
        $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'sentinel-migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sentinel');

        foreach ((array) config('sentinel.invariants', []) as $invariant) {
            $this->app->make(InvariantRegistry::class)->register($invariant);
        }

        $this->app->booted(fn () => $this->app->make(TriggerManager::class)->boot());
        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $this->app->make(ScheduleRegistrar::class)->register($schedule));

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListCommand::class, ShowCommand::class, CheckCommand::class, SweepCommand::class,
                DrainCommand::class, IncidentsCommand::class, IncidentActionCommand::class,
                RecheckCommand::class, PruneCommand::class, DoctorCommand::class,
                MakeInvariantCommand::class, RepairCommand::class,
            ]);
        }

        if (config('sentinel.dashboard.enabled', false) && ! $this->app->runningInConsole()) {
            Route::middleware(config('sentinel.dashboard.middleware', ['web']))
                ->get(config('sentinel.dashboard.path', 'sentinel'), [DashboardController::class, 'index'])
                ->name('sentinel.dashboard');
        }
    }
}
