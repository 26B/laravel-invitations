<?php

namespace TwentySixB\LaravelInvitations\Actions;

use Illuminate\Http\RedirectResponse;
use TwentySixB\LaravelInvitations\Models\Invitation;

class Expired {

	public static function handle(Invitation $invitation) : RedirectResponse
	{
		return redirect(config('invitations.fallback_route'));
	}
}
