<?php

namespace TwentySixB\LaravelInvitations\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use TwentySixB\LaravelInvitations\Models\Invitation;

class PurgeExpiredInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purges expired invitations when they become stale';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiration_in_days = config('invitations.purge.expiration_in_days', false);

		if ($expiration_in_days === false) {
			return Command::SUCCESS;
		}

        Invitation::orderBy('expires_at', 'ASC')
            ->where(
                'expires_at',
                '<',
                Carbon::now()->subDays($expiration_in_days)
            )
            ->limit(50)
            ->delete();

        return Command::SUCCESS;
    }
}
