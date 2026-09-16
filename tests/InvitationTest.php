<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use TwentySixB\LaravelInvitations\Events\InvitationAccepted;
use TwentySixB\LaravelInvitations\Events\InvitationCreated;
use TwentySixB\LaravelInvitations\Events\InvitationExpired;
use TwentySixB\LaravelInvitations\Events\InvitationRejected;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyAcceptedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyExpiredException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyRejectedException;
use TwentySixB\LaravelInvitations\Models\Invitation;
use TwentySixB\LaravelInvitations\Tests\Account;
use TwentySixB\LaravelInvitations\Tests\User;

test('accept sets accepted_at and dispatches the event', function () {
    Event::fake();

    $invitation = Invitation::factory()->forRecipient(recipient())->create();

    $invitation->accept();

    expect($invitation->accepted_at)->not->toBeNull()
        ->and($invitation->isAccepted())->toBeTrue();
    Event::assertDispatched(InvitationAccepted::class);
});

test('accept throws when already accepted', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');

    $invitation = Invitation::factory()->forRecipient(recipient())->create();
    $invitation->accept();

    $invitation->accept();
})->throws(InvitationAlreadyAcceptedException::class, 'This invitation has already been accepted on 2026-01-01 12:00:00.');

test('reject on an accepted invitation throws', function () {
    $invitation = Invitation::factory()->forRecipient(recipient())->create();
    $invitation->accept();

    $invitation->reject();
})->throws(InvitationAlreadyAcceptedException::class);

test('isExpired is consistent with the expired scope', function () {
    $expired = Invitation::factory()->expired()->forRecipient(recipient())->create();
    $resolvedPastDue = Invitation::factory()->expired()->accepted()->forRecipient(recipient())->create();

    expect($expired->isExpired())->toBeTrue()
        ->and($resolvedPastDue->isExpired())->toBeFalse();
});

test('accept on an expired invitation throws', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');

    $invitation = Invitation::factory()->expired()->forRecipient(recipient())->create();

    $invitation->accept();
})->throws(InvitationAlreadyExpiredException::class, 'This invitation has already expired on 2026-01-01 11:00:00.');

test('reject sets rejected_at and dispatches the event', function () {
    Event::fake();

    $invitation = Invitation::factory()->forRecipient(recipient())->create();

    $invitation->reject();

    expect($invitation->rejected_at)->not->toBeNull()
        ->and($invitation->isRejected())->toBeTrue();
    Event::assertDispatched(InvitationRejected::class);
});

test('reject throws when already rejected', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');

    $invitation = Invitation::factory()->forRecipient(recipient())->create();
    $invitation->reject();

    $invitation->reject();
})->throws(InvitationAlreadyRejectedException::class, 'This invitation has already been rejected on 2026-01-01 12:00:00.');

test('pending and expired scopes exclude resolved invitations', function () {
    $pending = Invitation::factory()->forRecipient(recipient())->create();
    $expired = Invitation::factory()->expired()->forRecipient(recipient())->create();
    $resolvedPastDue = Invitation::factory()->expired()->accepted()->forRecipient(recipient())->create();

    $pendingIds = Invitation::pending()->pluck('id');
    $expiredIds = Invitation::expired()->pluck('id');

    expect($pendingIds)->toContain($pending->id)
        ->and($pendingIds)->not->toContain($expired->id)
        ->and($expiredIds)->toContain($expired->id)
        ->and($expiredIds)->not->toContain($resolvedPastDue->id);
});

test('has invitations returns only invitations addressed to the model', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = Invitation::factory()->forRecipient($user)->create();
    Invitation::factory()->forRecipient($other)->create();

    $ids = $user->invitations()->where('code', $mine->code)->pluck('id');

    expect($ids->all())->toBe([$mine->id]);
});

test('invitations resolve their recipient and sender', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $invitation = Invitation::factory()->fromSender($sender)->forRecipient($recipient)->create();

    expect($invitation->sender->is($sender))->toBeTrue()
        ->and($invitation->recipient->is($recipient))->toBeTrue()
        ->and($recipient->invitations->first()->is($invitation))->toBeTrue();
});

test('the recipient and sender may view and delete the invitation', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $stranger = User::factory()->create();

    $invitation = Invitation::factory()->fromSender($sender)->forRecipient($recipient)->create();

    expect($recipient->can('view', $invitation))->toBeTrue()
        ->and($recipient->can('delete', $invitation))->toBeTrue()
        ->and($sender->can('view', $invitation))->toBeTrue()
        ->and($sender->can('delete', $invitation))->toBeTrue()
        ->and($stranger->can('view', $invitation))->toBeFalse()
        ->and($stranger->can('delete', $invitation))->toBeFalse();
});

test('an invitation without a sender is only visible to the recipient', function () {
    $recipient = User::factory()->create();
    $stranger = User::factory()->create();

    $invitation = Invitation::factory()->forRecipient($recipient)->create();

    expect($invitation->sender_type)->toBeNull()
        ->and($recipient->can('view', $invitation))->toBeTrue()
        ->and($stranger->can('view', $invitation))->toBeFalse();
});

test('policy matches models with integer keys', function () {
    $sender = Account::create();
    $recipient = Account::create();
    $stranger = Account::create();

    $invitation = Invitation::factory()->fromSender($sender)->forRecipient($recipient)->create();

    expect(Gate::forUser($recipient)->allows('view', $invitation))->toBeTrue()
        ->and(Gate::forUser($recipient)->allows('delete', $invitation))->toBeTrue()
        ->and(Gate::forUser($sender)->allows('view', $invitation))->toBeTrue()
        ->and(Gate::forUser($stranger)->allows('view', $invitation))->toBeFalse();
});

test('accept is atomic against a concurrent accept', function () {
    $invitation = Invitation::factory()->forRecipient(recipient())->create();
    $stale = Invitation::find($invitation->id);

    $invitation->accept();

    $stale->accept();
})->throws(InvitationAlreadyAcceptedException::class);

test('reject is atomic against a stalled concurrent accept', function () {
    $invitation = Invitation::factory()->forRecipient(recipient())->create();
    $stale = Invitation::find($invitation->id);

    $invitation->accept();

    $stale->reject();
})->throws(InvitationAlreadyAcceptedException::class);

test('reject is atomic against a stalled concurrent reject', function () {
    $invitation = Invitation::factory()->forRecipient(recipient())->create();
    $stale = Invitation::find($invitation->id);

    $invitation->reject();

    $stale->reject();
})->throws(InvitationAlreadyRejectedException::class);

test('purge removes only stamped expired invitations past the cutoff by default', function () {
    $expired = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(40)]);
    $undispatched = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(40)]);
    $pending = Invitation::factory()->forRecipient(recipient())->pending()->create();
    $accepted = Invitation::factory()->forRecipient(recipient())->accepted()->create(['expires_at' => now()->subDays(40)]);
    $rejected = Invitation::factory()->forRecipient(recipient())->rejected()->create(['expires_at' => now()->subDays(40)]);

    $expired->forceFill(['expired_dispatched_at' => now()])->save();

    $this->artisan('invitations:purge')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull()
        ->and(Invitation::find($undispatched->id))->not->toBeNull()
        ->and(Invitation::find($pending->id))->not->toBeNull()
        ->and(Invitation::find($accepted->id))->not->toBeNull()
        ->and(Invitation::find($rejected->id))->not->toBeNull();
});

test('dispatch-expired stamps and dispatches each expired invitation once', function () {
    Event::fake();

    $expired = Invitation::factory()->forRecipient(recipient())->expired()->create();
    $recent = Invitation::factory()->forRecipient(recipient())->pending()->create();

    $this->artisan('invitations:dispatch-expired')->assertSuccessful();
    $this->artisan('invitations:dispatch-expired')->assertSuccessful();

    Event::assertDispatched(InvitationExpired::class, 1);

    expect($expired->fresh()->expired_dispatched_at)->not->toBeNull()
        ->and($recent->fresh()->expired_dispatched_at)->toBeNull();
});

test('expire sets expires_at to now, stamps the dispatch and fires the event', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');
    Event::fake([InvitationExpired::class]);

    $invitation = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->addDays(7)]);

    expect($invitation->isPending())->toBeTrue();

    $invitation->expire();

    expect($invitation->isExpired())->toBeTrue()
        ->and($invitation->isPending())->toBeFalse()
        ->and($invitation->expires_at->toDateTimeString())->toBe('2026-01-01 12:00:00')
        ->and($invitation->fresh()->expired_dispatched_at)->not->toBeNull();

    Event::assertDispatched(InvitationExpired::class);
});

test('expire leaves resolved invitations untouched', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');
    Event::fake([InvitationExpired::class]);

    $invitation = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->addDays(7)]);
    $invitation->accept();

    $invitation->expire();

    expect($invitation->isAccepted())->toBeTrue()
        ->and($invitation->expires_at->toDateTimeString())->toBe('2026-01-08 12:00:00')
        ->and($invitation->fresh()->expired_dispatched_at)->toBeNull();

    Event::assertNotDispatched(InvitationExpired::class);
});

test('an invitation expired by expire() is not dispatched again by the command', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');
    Event::fake([InvitationExpired::class]);

    $invitation = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->addDays(7)]);
    $invitation->expire();

    $this->artisan('invitations:dispatch-expired')->assertSuccessful();

    Event::assertDispatched(InvitationExpired::class, 1);
});

test('purge with force removes undispatched expired invitations', function () {
    $expired = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(40)]);
    $recent = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(5)]);

    $this->artisan('invitations:purge --force --days=30')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull()
        ->and(Invitation::find($recent->id))->not->toBeNull();
});

test('purge leaves undispatched expired invitations for the dispatch job', function () {
    $expired = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge')->assertSuccessful();
    $this->artisan('invitations:dispatch-expired')->assertSuccessful();

    with(Invitation::find($expired->id), fn ($row) => $row->forceFill(['expired_dispatched_at' => now()])->save());

    $this->artisan('invitations:purge')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull();
});

test('purge can target accepted invitations', function () {
    $accepted = Invitation::factory()->forRecipient(recipient())->accepted()->create(['expires_at' => now()->subDays(40)]);
    $rejected = Invitation::factory()->forRecipient(recipient())->rejected()->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge --accepted')->assertSuccessful();

    expect(Invitation::find($accepted->id))->toBeNull()
        ->and(Invitation::find($rejected->id))->not->toBeNull();
});

test('purge can target rejected invitations', function () {
    $accepted = Invitation::factory()->forRecipient(recipient())->accepted()->create(['expires_at' => now()->subDays(40)]);
    $rejected = Invitation::factory()->forRecipient(recipient())->rejected()->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge --rejected')->assertSuccessful();

    expect(Invitation::find($accepted->id))->not->toBeNull()
        ->and(Invitation::find($rejected->id))->toBeNull();
});

test('purge can target all states', function () {
    $expired = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(40)]);
    $accepted = Invitation::factory()->forRecipient(recipient())->accepted()->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge --all')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull()
        ->and(Invitation::find($accepted->id))->toBeNull();
});

test('purge respects the days cutoff', function () {
    $older = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(40)]);
    $recent = Invitation::factory()->forRecipient(recipient())->create(['expires_at' => now()->subDays(5)]);

    $older->forceFill(['expired_dispatched_at' => now()])->save();

    $this->artisan('invitations:purge --days=30')->assertSuccessful();

    expect(Invitation::find($older->id))->toBeNull()
        ->and(Invitation::find($recent->id))->not->toBeNull();
});

test('code is unique', function () {
    $code = Str::uuid();

    Invitation::factory()->forRecipient(recipient())->create(['code' => $code]);

    Invitation::factory()->forRecipient(recipient())->create(['code' => $code]);
})->throws(QueryException::class);

test('creating an invitation dispatches the created event', function () {
    Event::fake([InvitationCreated::class]);

    $invitation = Invitation::factory()->forRecipient(recipient())->create();

    Event::assertDispatched(
        InvitationCreated::class,
        fn (InvitationCreated $event) => $event->getInvitation()->is($invitation),
    );
});
