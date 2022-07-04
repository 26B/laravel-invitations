<?php

namespace TwentySixB\LaravelInvitations;

use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use TwentySixB\LaravelInvitations\Http\Livewire\Invitations;
use TwentySixB\LaravelInvitations\Http\Livewire\InviteUsers;
use Livewire\Livewire;

/**
 * Package Service Provider
 *
 */
class InvitationsServiceProvider extends PackageServiceProvider
{

    /**
     * @inheritDoc
     *
     * @param Package $package
     * @return void
     */
    public function configurePackage(Package $package) : void
    {
        $package->name('laravel-invitations')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasViews('invitations');
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function packageBooted() : void
    {
        Livewire::component('invitations.invites', Invitations::class);
        Livewire::component('invitations.inviter', InviteUsers::class);

        // TODO: Register all livewire components.
    }
}
