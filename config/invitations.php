<?php

use TwentySixB\LaravelInvitations\Actions\Accept;
use TwentySixB\LaravelInvitations\Actions\Delete;
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
	],

	/**
	 * Action classes that can customized.
	 *
	 */
	'actions' => [
		'accept' => Accept::class,
		'expired' => Expired::class,
		'reject' => Reject::class,
		'delete' => Delete::class,
	],

	/**
	 * Used as redirect when no other route is available.
	 */
	'fallback_route' => 'dashboard',

	'purge' => [

		/**
		 * Determines how old invitations should be before purging them.
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
		'user' => \App\Models\User::class,

		/**
		 * Model that handles the invitations.
		 *
		 */
		'invitation' => \TwentySixB\LaravelInvitations\Models\Invitation::class,
	],

];
