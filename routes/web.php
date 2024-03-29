<?php

use TwentySixB\LaravelInvitations\Http\Controllers\InvitationController;
use TwentySixB\LaravelInvitations\Http\Livewire\Viewer;

// Invitations
Route::get('/invitation/{invitation_id}', Viewer::class)->name('invitation.show');

Route::get('/qrcode/scanned/{model}/{id}/{code}', [InvitationController::class, 'validateCode'])
	->name('invite.qrcode.scanned');
