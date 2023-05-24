<?php

namespace TwentySixB\LaravelInvitations\Actions;

use TwentySixB\LaravelInvitations\Models\Invitation;

class Reject {

	public static function handle(Invitation $invitation) : \Livewire\Redirector
	{
		// Delete or mark as expired?
		$invitation->delete();

		return redirect()
			->route(config('invitations.fallback_route'));
	}
}
