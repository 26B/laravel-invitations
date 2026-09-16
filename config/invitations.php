<?php

use App\Models\User;
use TwentySixB\LaravelInvitations\Models\Invitation;

return [

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
         */
        'user' => User::class,

        /**
         * Model that handles the invitations.
         */
        'invitation' => Invitation::class,
    ],

];
