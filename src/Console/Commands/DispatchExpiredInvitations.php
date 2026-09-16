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
    protected $description = 'Dispatches an InvitationExpired event for every expired invitation';

    /**
     * Execute the console command.
     *
     * ponytail: at-least-once delivery, fires on every run, no watermark column.
     * Add a dispatched_at column when duplicate events matter.
     */
    public function handle(): int
    {
        Invitation::expired()
            ->orderBy('expires_at', 'ASC')
            ->limit(50)
            ->get()
            ->each(fn (Invitation $invitation) => InvitationExpired::dispatch($invitation));

        return Command::SUCCESS;
    }
}