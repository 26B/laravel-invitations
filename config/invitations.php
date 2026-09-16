<?php

return [

    'purge' => [

        /**
         * Default cutoff (in days) for `invitations:purge`: how long past
         * `expires_at` an invitation must be before being purged.
         *
         * Set to false if you don't want purging to remove anything by default.
         * Override per-run with the `--days=` command argument.
         */
        'expired_days' => 30,
    ],

];
