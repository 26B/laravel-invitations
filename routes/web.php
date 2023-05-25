<?php

use TwentySixB\LaravelInvitations\Http\Controllers\InvitationController;
use TwentySixB\LaravelInvitations\Http\Livewire\Viewer;
use TwentySixB\LaravelInvitations\Models\Invitation;

// Might not need it, resolving as string.
// Route::model('invitation', Invitation::class);

// TODO: Refactor into customizable routes.

// Invitations
// Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

	Route::get('/invitation/{invitation_id}', Viewer::class)->name('invitation.show');

    Route::get('/event/{model_id}/attending/invite/{code}', [InvitationController::class, 'validateCode'])
        ->name('event.invite.scanned');
    Route::get('/group/{model_id}/member/invite/{code}', [InvitationController::class, 'validateCode'])
        ->name('group.invite.scanned');
// });
