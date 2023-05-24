<?php

namespace TwentySixB\LaravelInvitations\Actions;

use Illuminate\Support\Facades\Gate;
use TwentySixB\LaravelInvitations\Models\Invitation;

class Delete {

	public static function handle(Invitation $invitation)
	{
		if (! Gate::allows('delete', $invitation)) {
            abort(403);
        }

		// $invitation->delete();

	}
}
