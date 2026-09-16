<?php

namespace TwentySixB\LaravelInvitations\Exceptions;

use DateTimeInterface;
use Exception;

class InvitationAlreadyAcceptedException extends Exception
{
    public function __construct(public readonly DateTimeInterface $acceptedAt)
    {
        parent::__construct('This invitation has already been accepted on '.$acceptedAt->format('Y-m-d H:i:s').'.');
    }
}
