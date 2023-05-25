<?php

namespace TwentySixB\LaravelInvitations\Http\Controllers;


use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TwentySixB\LaravelInvitations\Models\Invitation;

class InvitationController extends Controller
{
    // use AuthorizesRequests;

    /**
     * After a user scans a QR code it is redirected to this endpoint.
     */
    public function validateCode(Request $request, string $id, string $code): RedirectResponse
    {
        $type = Arr::first(explode('/', $request->path()));
        $model = Str::of($type)->studly();
        /** @var \App\Models\Model */
        $row = "\App\Models\\$model"::whereId($id)->firstOrFail();

        // Validate code.
        if ($row->invite_code !== $code) {
            throw new HttpException(422, __('Invitation code not valid'));
        }

        InviteCodeUsed::dispatch($row);

        return redirect()->route("{$type}.show", $row);
    }
}
