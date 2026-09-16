<?php

namespace TwentySixB\LaravelInvitations\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use TwentySixB\LaravelInvitations\Models\Invitation;

trait HasInvitations
{
    public function invitations(): MorphMany
    {
        return $this->morphMany(Invitation::class, 'invitable');
    }
}
