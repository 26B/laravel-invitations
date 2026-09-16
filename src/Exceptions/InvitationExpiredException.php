<?php

namespace TwentySixB\LaravelInvitations\Exceptions;

use DateTimeInterface;
use Exception;

class InvitationExpiredException extends Exception
{
    public function __construct(public readonly DateTimeInterface $expiredAt)
    {
        parent::__construct('This invitation has expired on '.$expiredAt->format('Y-m-d H:i:s').'.');
    }
}
