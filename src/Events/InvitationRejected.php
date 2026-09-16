<?php

namespace TwentySixB\LaravelInvitations\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TwentySixB\LaravelInvitations\Models\Invitation;

class InvitationRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        protected Invitation $invitation
    ) {}

    /**
     * Returns the Invitation instance.
     */
    public function getInvitation(): Invitation
    {
        return $this->invitation;
    }
}
