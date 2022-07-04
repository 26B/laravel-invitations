<?php

namespace TwentySixB\LaravelInvitations\Exceptions;

use Exception;

class InvitationExpiredException extends Exception {

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return view('invitations::errors.expired')->with('exception', $this);;
    }
}
