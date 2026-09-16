<?php

namespace TwentySixB\LaravelInvitations\Exceptions;

use DateTimeInterface;
use Exception;

class InvitationAlreadyExpiredException extends Exception
{
    public function __construct(public readonly DateTimeInterface $expiredAt)
    {
        parent::__construct('This invitation has already expired on '.$expiredAt->format('Y-m-d H:i:s').'.');
    }
}
