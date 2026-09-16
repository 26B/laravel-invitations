<?php

namespace TwentySixB\LaravelInvitations\Actions;

use Illuminate\Http\RedirectResponse;
use TwentySixB\LaravelInvitations\Models\Invitation;
use TwentySixB\LaravelInvitations\Events\InvitationAccepted;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Accept {

	public static function handle(Invitation $invitation) : RedirectResponse
	{
		$invitation->use()->save();
		InvitationAccepted::dispatch($invitation);

		$route = config('invitations.fallback_route');

		if (isset($invitation->data['redirect'])) {
			$route = $invitation->data['redirect'];
		}

		return redirect()
			->route($route);
	}
}
