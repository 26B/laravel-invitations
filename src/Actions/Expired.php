<?php

namespace TwentySixB\LaravelInvitations\Actions;

use TwentySixB\LaravelInvitations\Models\Invitation;

class Expired {

	public static function handle(Invitation $invitation) : \Livewire\Redirector
	{
		return redirect(config('invitations.fallback_route'));
	}
}
