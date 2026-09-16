<?php

namespace TwentySixB\LaravelInvitations\Exceptions;

use Exception;

class InvitationExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('This invitation has expired.');
    }
}
