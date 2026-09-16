<?php

namespace TwentySixB\LaravelInvitations\Console\Commands;

use Illuminate\Console\Command;
use TwentySixB\LaravelInvitations\Events\InvitationExpired;
use TwentySixB\LaravelInvitations\Models\Invitation;

class DispatchExpiredInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:dispatch-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches an InvitationExpired event for every expired invitation whose expiry has not been dispatched yet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = 0;

        foreach (Invitation::expired()
            ->whereNull('expired_dispatched_at')
            ->orderBy('expires_at', 'asc')
            ->limit(50)
            ->get() as $invitation) {
            InvitationExpired::dispatch($invitation);
            $invitation->forceFill(['expired_dispatched_at' => now()])->save();
            $count++;
        }

        $this->info("Dispatched InvitationExpired for {$count} invitation(s).");

        return Command::SUCCESS;
    }
}
