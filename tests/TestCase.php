<?php

namespace TwentySixB\LaravelInvitations\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \TwentySixB\LaravelInvitations\InvitationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('invitations.models.user', \TwentySixB\LaravelInvitations\Tests\User::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom([
            __DIR__ . '/migrations',
            __DIR__ . '/../database/migrations',
        ]);
    }

    protected function invitable(): Invitable
    {
        $invitable = new Invitable();
        $invitable->id = (string) \Illuminate\Support\Str::uuid();
        $invitable->save();

        return $invitable;
    }
}