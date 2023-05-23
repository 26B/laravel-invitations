<?php

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
		'user' => null,

		/**
		 * Model that handles the invitations.
		 *
		 */
		'invitation' => \TwentySixB\LaravelInvitations\Models\Invitation::class,
	],


];
