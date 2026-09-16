<?php

namespace TwentySixB\LaravelInvitations\Exceptions;

use DateTimeInterface;
use Exception;

class InvitationAlreadyRejectedException extends Exception
{
    public function __construct(public readonly DateTimeInterface $rejectedAt)
    {
        parent::__construct('This invitation has already been rejected on '.$rejectedAt->format('Y-m-d H:i:s').'.');
    }
}
