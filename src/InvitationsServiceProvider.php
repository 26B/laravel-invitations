<?php

namespace TwentySixB\LaravelInvitations;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use TwentySixB\LaravelInvitations\Console\Commands\PurgeExpiredInvitations;
use TwentySixB\LaravelInvitations\Http\Livewire\InviteUsers;
use TwentySixB\LaravelInvitations\Http\Livewire\AcceptInvitation;
use TwentySixB\LaravelInvitations\Http\Livewire\InvitationList;

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
			->hasMigration('create_invitations_table')
            ->hasRoute('web')
			->hasCommand(PurgeExpiredInvitations::class)
            ->hasViews('invitations');
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function packageBooted() : void
    {
		// TODO: Enable livewire components.
		/*
        Livewire::component('invitations.list', InvitationList::class);
        Livewire::component('invitations.inviter', InviteUsers::class);
		Livewire::component('invitations.accept', AcceptInvitation::class);
		*/
    }
}
