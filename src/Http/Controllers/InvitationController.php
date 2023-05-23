<?php

namespace TwentySixB\LaravelInvitations\Http\Controllers;

use chillerlan\QRCode\QRCode;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TwentySixB\LaravelInvitations\Models\Invitation;

class InvitationController extends Controller
{
    // use AuthorizesRequests;

    /**
     * Displays QR Code for scanning.
     */
    public function showCode(Request $request, string $id): View
    {
        $type = Arr::first(explode('/', $request->path()));
        $model = Str::of($type)->studly();

        /** @var \App\Models\Model $model */
        $row = "\App\Models\\{$model}"::whereId($id)->firstOrFail();

        $this->authorize('invite', $row);

        if (empty($row->invite_code) && method_exists($row, 'regenerateInviteCode')) {
            $row->regenerateInviteCode()->save();
        }

        $route = route(
            "{$type}.invite.scanned",
            [
                'model_id' => $row->id,
                'code' => $row->invite_code,
            ]
        );
        $qrcode = (new QRCode)->render($route);

        return view(Str::plural($type).'.invite')
            ->with('qrcode', $qrcode)
            ->with('model', $row);
    }

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
