<?php

namespace TwentySixB\LaravelInvitations\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TwentySixB\LaravelInvitations\InvitationsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            InvitationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom([
            __DIR__.'/migrations',
            __DIR__.'/../database/migrations',
        ]);
    }
}
