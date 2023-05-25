<?php

namespace TwentySixB\LaravelInvitations\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TwentySixB\LaravelInvitations\Events\InviteCodeUsed;
use TwentySixB\LaravelInvitations\Exceptions\InvalidCodeException;

class InvitationController extends Controller
{

	/**
     * After a user scans a QR code it is redirected to this endpoint.
	 *
	 * Here we attempt to do some guesswork so all you have to do is handle the InviteCodeUsed event.
     */
    public function validateCode(Request $request, string $model, string $id, string $code): RedirectResponse
    {
		if (auth()->check() === false) {
			abort(403);
		}

        $class = Str::of($model)->studly();
        /** @var \App\Models\Model */
        $row = "\App\Models\\$class"::whereId($id)->firstOrFail();

        // Validate code.
        if ($row->invite_code !== $code) {
            throw new InvalidCodeException(422, __('Invitation code not valid'));
        }

		// The part you need to handle.
        InviteCodeUsed::dispatch($row);

        return redirect()->route("{$model}.show", $row);
    }
}
