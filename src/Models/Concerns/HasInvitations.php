<?php

namespace TwentySixB\LaravelInvitations\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use TwentySixB\LaravelInvitations\Models\Invitation;

trait HasInvitations {

	public function invitations() : Builder
    {
        return Invitation::where('data->user->id', $this->getKey())
			->orWhere('data->user->email', $this->email);
    }
}
