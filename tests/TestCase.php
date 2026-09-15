<?php

namespace Evolvex\InvariantSentinel\Tests;

use Evolvex\InvariantSentinel\SentinelServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array { return [SentinelServiceProvider::class]; }
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('cache.default', 'array'); $app['config']->set('queue.default', 'sync');
    }
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Schema::create('test_subjects', function (Blueprint $t) { $t->id(); $t->string('status'); $t->timestamps(); });
    }
}
