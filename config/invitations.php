<?php

use TwentySixB\LaravelInvitations\Actions\Accept;
use TwentySixB\LaravelInvitations\Actions\Expired;
use TwentySixB\LaravelInvitations\Actions\Reject;

return [

    /**
     * Models that are invitable.
     *
     * @var array
     */
    'invitables' => [
		//
		// TODO: Leave empty.
		\App\Models\Event::class,
		\App\Models\Group::class,
	],

	'actions' => [
		'accept' => Accept::class,
		'expired' => Expired::class,
		'reject' => Reject::class,
	],

	'fallback_route' => 'dashboard',

	'purge' => [

		/**
		 * Determins how old invitations should be before purging them.
		 *
		 * Set to false if you don't want to remove expired invitations.
		 */
		'expiration_in_days' => 30,
	],

	'models' => [

		/**
		 * User model.
		 *
		 */
		// FIXME: Dont push this.
		'user' => \App\Models\User::class,

		/**
		 * Model that handles the invitations.
		 *
		 */
		'invitation' => \TwentySixB\LaravelInvitations\Models\Invitation::class,
	],


];
