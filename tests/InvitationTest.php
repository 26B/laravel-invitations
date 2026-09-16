<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use TwentySixB\LaravelInvitations\Events\InvitationAccepted;
use TwentySixB\LaravelInvitations\Events\InvitationExpired;
use TwentySixB\LaravelInvitations\Events\InvitationRejected;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyAcceptedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyRejectedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationExpiredException;
use TwentySixB\LaravelInvitations\Models\Invitation;
use TwentySixB\LaravelInvitations\Tests\Account;
use TwentySixB\LaravelInvitations\Tests\User;

test('accept sets accepted_at and dispatches the event', function () {
    Event::fake();

    $invitation = Invitation::factory()->forInvitable(invitable())->create();

    $invitation->accept();

    expect($invitation->accepted_at)->not->toBeNull()
        ->and($invitation->isAccepted())->toBeTrue();
    Event::assertDispatched(InvitationAccepted::class);
});

test('accept throws when already accepted', function () {
    $invitation = Invitation::factory()->forInvitable(invitable())->create();
    $invitation->accept();

    $invitation->accept();
})->throws(InvitationAlreadyAcceptedException::class);

test('reject on an accepted invitation throws', function () {
    $invitation = Invitation::factory()->forInvitable(invitable())->create();
    $invitation->accept();

    $invitation->reject();
})->throws(InvitationAlreadyAcceptedException::class);

test('isExpired is consistent with the expired scope', function () {
    $expired = Invitation::factory()->expired()->forInvitable(invitable())->create();
    $resolvedPastDue = Invitation::factory()->expired()->accepted()->forInvitable(invitable())->create();

    expect($expired->isExpired())->toBeTrue()
        ->and($resolvedPastDue->isExpired())->toBeFalse();
});

test('accept on an expired invitation throws', function () {
    $invitation = Invitation::factory()->expired()->forInvitable(invitable())->create();

    $invitation->accept();
})->throws(InvitationExpiredException::class);

test('reject sets rejected_at and dispatches the event', function () {
    Event::fake();

    $invitation = Invitation::factory()->forInvitable(invitable())->create();

    $invitation->reject();

    expect($invitation->rejected_at)->not->toBeNull()
        ->and($invitation->isRejected())->toBeTrue();
    Event::assertDispatched(InvitationRejected::class);
});

test('reject throws when already rejected', function () {
    $invitation = Invitation::factory()->forInvitable(invitable())->create();
    $invitation->reject();

    $invitation->reject();
})->throws(InvitationAlreadyRejectedException::class);

test('active and expired scopes exclude resolved invitations', function () {
    $active = Invitation::factory()->forInvitable(invitable())->create();
    $expired = Invitation::factory()->expired()->forInvitable(invitable())->create();
    $resolvedPastDue = Invitation::factory()->expired()->accepted()->forInvitable(invitable())->create();

    $activeIds = Invitation::active()->pluck('id');
    $expiredIds = Invitation::expired()->pluck('id');

    expect($activeIds)->toContain($active->id)
        ->and($activeIds)->not->toContain($expired->id)
        ->and($expiredIds)->toContain($expired->id)
        ->and($expiredIds)->not->toContain($resolvedPastDue->id);
});

test('has invitations returns only invitations addressed to the model', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = Invitation::factory()->forInvitable($user)->create();
    Invitation::factory()->forInvitable($other)->create();

    $ids = $user->invitations()->where('code', $mine->code)->pluck('id');

    expect($ids->all())->toBe([$mine->id]);
});

test('invitations resolve their invitable and author', function () {
    $author = User::factory()->create();
    $recipient = User::factory()->create();

    $invitation = Invitation::factory()->from($author)->forInvitable($recipient)->create();

    expect($invitation->author->is($author))->toBeTrue()
        ->and($invitation->invitable->is($recipient))->toBeTrue()
        ->and($recipient->invitations->first()->is($invitation))->toBeTrue();
});

test('the recipient and author may view and delete the invitation', function () {
    $author = User::factory()->create();
    $recipient = User::factory()->create();
    $stranger = User::factory()->create();

    $invitation = Invitation::factory()->from($author)->forInvitable($recipient)->create();

    expect($recipient->can('view', $invitation))->toBeTrue()
        ->and($recipient->can('delete', $invitation))->toBeTrue()
        ->and($author->can('view', $invitation))->toBeTrue()
        ->and($author->can('delete', $invitation))->toBeTrue()
        ->and($stranger->can('view', $invitation))->toBeFalse()
        ->and($stranger->can('delete', $invitation))->toBeFalse();
});

test('an invitation without an author is only visible to the recipient', function () {
    $recipient = User::factory()->create();
    $stranger = User::factory()->create();

    $invitation = Invitation::factory()->forInvitable($recipient)->create();

    expect($invitation->author_type)->toBeNull()
        ->and($recipient->can('view', $invitation))->toBeTrue()
        ->and($stranger->can('view', $invitation))->toBeFalse();
});

test('policy matches models with integer keys', function () {
    $author = Account::create();
    $recipient = Account::create();
    $stranger = Account::create();

    $invitation = Invitation::factory()->from($author)->forInvitable($recipient)->create();

    expect(Gate::forUser($recipient)->allows('view', $invitation))->toBeTrue()
        ->and(Gate::forUser($recipient)->allows('delete', $invitation))->toBeTrue()
        ->and(Gate::forUser($author)->allows('view', $invitation))->toBeTrue()
        ->and(Gate::forUser($stranger)->allows('view', $invitation))->toBeFalse();
});

test('accept is atomic against a concurrent accept', function () {
    $invitation = Invitation::factory()->forInvitable(invitable())->create();
    $stale = Invitation::find($invitation->id);

    $invitation->accept();

    $stale->accept();
})->throws(InvitationAlreadyAcceptedException::class);

test('reject is atomic against a stalled concurrent accept', function () {
    $invitation = Invitation::factory()->forInvitable(invitable())->create();
    $stale = Invitation::find($invitation->id);

    $invitation->accept();

    $stale->reject();
})->throws(InvitationAlreadyAcceptedException::class);

test('reject is atomic against a stalled concurrent reject', function () {
    $invitation = Invitation::factory()->forInvitable(invitable())->create();
    $stale = Invitation::find($invitation->id);

    $invitation->reject();

    $stale->reject();
})->throws(InvitationAlreadyRejectedException::class);

test('purge removes only stamped expired invitations past the cutoff by default', function () {
    $expired = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(40)]);
    $unreported = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(40)]);
    $pending = Invitation::factory()->forInvitable(invitable())->pending()->create();
    $accepted = Invitation::factory()->forInvitable(invitable())->accepted()->create(['expires_at' => now()->subDays(40)]);
    $rejected = Invitation::factory()->forInvitable(invitable())->rejected()->create(['expires_at' => now()->subDays(40)]);

    $expired->forceFill(['expired_dispatched_at' => now()])->save();

    $this->artisan('invitations:purge')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull()
        ->and(Invitation::find($unreported->id))->not->toBeNull()
        ->and(Invitation::find($pending->id))->not->toBeNull()
        ->and(Invitation::find($accepted->id))->not->toBeNull()
        ->and(Invitation::find($rejected->id))->not->toBeNull();
});

test('dispatch-expired stamps and reports each expired invitation once', function () {
    Event::fake();

    $expired = Invitation::factory()->forInvitable(invitable())->expired()->create();
    $recent = Invitation::factory()->forInvitable(invitable())->pending()->create();

    $this->artisan('invitations:dispatch-expired')->assertSuccessful();
    $this->artisan('invitations:dispatch-expired')->assertSuccessful();

    Event::assertDispatched(InvitationExpired::class, 1);

    expect($expired->fresh()->expired_dispatched_at)->not->toBeNull()
        ->and($recent->fresh()->expired_dispatched_at)->toBeNull();
});

test('purge with force removes unreported expired invitations', function () {
    $expired = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(40)]);
    $recent = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(5)]);

    $this->artisan('invitations:purge --force --days=30')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull()
        ->and(Invitation::find($recent->id))->not->toBeNull();
});

test('purge leaves unreported expired invitations for the dispatch job', function () {
    $expired = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge')->assertSuccessful();
    $this->artisan('invitations:dispatch-expired')->assertSuccessful();

    with(Invitation::find($expired->id), fn ($row) => $row->forceFill(['expired_dispatched_at' => now()])->save());

    $this->artisan('invitations:purge')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull();
});

test('purge can target accepted invitations', function () {
    $accepted = Invitation::factory()->forInvitable(invitable())->accepted()->create(['expires_at' => now()->subDays(40)]);
    $rejected = Invitation::factory()->forInvitable(invitable())->rejected()->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge --accepted')->assertSuccessful();

    expect(Invitation::find($accepted->id))->toBeNull()
        ->and(Invitation::find($rejected->id))->not->toBeNull();
});

test('purge can target rejected invitations', function () {
    $accepted = Invitation::factory()->forInvitable(invitable())->accepted()->create(['expires_at' => now()->subDays(40)]);
    $rejected = Invitation::factory()->forInvitable(invitable())->rejected()->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge --rejected')->assertSuccessful();

    expect(Invitation::find($accepted->id))->not->toBeNull()
        ->and(Invitation::find($rejected->id))->toBeNull();
});

test('purge can target all states', function () {
    $expired = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(40)]);
    $accepted = Invitation::factory()->forInvitable(invitable())->accepted()->create(['expires_at' => now()->subDays(40)]);

    $this->artisan('invitations:purge --all')->assertSuccessful();

    expect(Invitation::find($expired->id))->toBeNull()
        ->and(Invitation::find($accepted->id))->toBeNull();
});

test('purge respects the days cutoff', function () {
    $older = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(40)]);
    $recent = Invitation::factory()->forInvitable(invitable())->create(['expires_at' => now()->subDays(5)]);

    $older->forceFill(['expired_dispatched_at' => now()])->save();

    $this->artisan('invitations:purge --days=30')->assertSuccessful();

    expect(Invitation::find($older->id))->toBeNull()
        ->and(Invitation::find($recent->id))->not->toBeNull();
});

test('code is unique', function () {
    $code = Str::uuid();

    Invitation::factory()->forInvitable(invitable())->create(['code' => $code]);

    Invitation::factory()->forInvitable(invitable())->create(['code' => $code]);
})->throws(QueryException::class);
