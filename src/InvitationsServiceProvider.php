<?php

namespace TwentySixB\LaravelInvitations;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;
use TwentySixB\LaravelInvitations\Console\Commands\PurgeExpiredInvitations;
use TwentySixB\LaravelInvitations\Http\Livewire\InviteUsers;
use TwentySixB\LaravelInvitations\Http\Livewire\AcceptInvitation;
use TwentySixB\LaravelInvitations\Http\Livewire\InvitationList;
use TwentySixB\LaravelInvitations\Models\Invitation;
use TwentySixB\LaravelInvitations\Policies\InvitationPolicy;

/**
 * Package Service Provider
 *
 */
class InvitationsServiceProvider extends PackageServiceProvider
{

	/**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
		// Invitation::class => null,
        // Invitation::class => InvitationPolicy::class,
    ];

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
			// FIXME: Invitations model is not recognized for routing.
            // ->hasRoute('web')
			// ->publishesServiceProvider(InvitationsServiceProvider::class)
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
        Livewire::component('invitations.list', InvitationList::class);
        Livewire::component('invitations.inviter', InviteUsers::class);
		Livewire::component('invitations.accept', AcceptInvitation::class);

    }
}
