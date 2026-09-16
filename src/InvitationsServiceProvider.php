<?php

namespace TwentySixB\LaravelInvitations;

use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TwentySixB\LaravelInvitations\Console\Commands\DispatchExpiredInvitations;
use TwentySixB\LaravelInvitations\Console\Commands\PurgeExpiredInvitations;
use TwentySixB\LaravelInvitations\Policies\InvitationPolicy;

/**
 * Package Service Provider
 */
class InvitationsServiceProvider extends PackageServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-invitations')
            ->hasConfigFile()
            ->hasMigrations([
                'create_invitations_table',
                'alter_invitations_table_add_accepted_and_rejected_at',
            ])
            ->hasCommands([
                DispatchExpiredInvitations::class,
                PurgeExpiredInvitations::class,
            ]);
    }

    /**
     * {@inheritDoc}
     */
    public function packageBooted(): void
    {
        Gate::policy(config('invitations.models.invitation'), InvitationPolicy::class);
    }
}
