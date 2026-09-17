<?php

namespace TwentySixB\LaravelInvitations\Tests;

use Illuminate\Database\Eloquent\Model;
use TwentySixB\LaravelInvitations\Policies\InvitationPolicy;

class ExtendedInvitationPolicy extends InvitationPolicy
{
    public function before(Model $user, string $ability, mixed ...$arguments): ?bool
    {
        if ($ability === 'view') {
            return true;
        }

        if ($ability === 'delete') {
            return false;
        }

        return null;
    }
}
