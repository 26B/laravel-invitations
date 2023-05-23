<?php

use TwentySixB\LaravelInvitations\Http\Controllers\InvitationController;
use TwentySixB\LaravelInvitations\Http\Livewire\InvitationViewer;
use TwentySixB\LaravelInvitations\Models\Invitation;

// Doesnt seem to be needed.
Route::model('invitation', Invitation::class);

// TODO: Refactor into customizable routes.

// Invitations
// Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

	Route::get('/invitation/{invitation}', InvitationViewer::class)->name('invitation.show');

    // QR Codes
    Route::get('/event/{model_id}/attending/invite', [InvitationController::class, 'showCode'])
        ->name('event.invite');
    Route::get('/group/{model_id}/member/invite', [InvitationController::class, 'showCode'])
        ->name('group.invite');

    Route::get('/event/{model_id}/attending/invite/{code}', [InvitationController::class, 'validateCode'])
        ->name('event.invite.scanned');
    Route::get('/group/{model_id}/member/invite/{code}', [InvitationController::class, 'validateCode'])
        ->name('group.invite.scanned');
// });
