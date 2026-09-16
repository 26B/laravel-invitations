<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TwentySixB\LaravelInvitations\Events\InvitationAccepted;
use TwentySixB\LaravelInvitations\Events\InvitationExpired;
use TwentySixB\LaravelInvitations\Events\InvitationRejected;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyAcceptedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyRejectedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationExpiredException;
use TwentySixB\LaravelInvitations\Models\Invitation;
use TwentySixB\LaravelInvitations\Tests\Invitable;
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

test('has invitations groups the OR clause so chained constraints apply to both branches', function () {
    $user = User::factory()->create(['email' => 'a@b.c']);
    $other = User::factory()->create(['email' => 'a@b.c']);

    $forUser = Invitation::factory()->forInvitable(invitable())->create(['data' => ['user' => ['id' => $user->id]]]);
    Invitation::factory()->forInvitable(invitable())->create(['data' => ['user' => ['id' => $other->id]]]);

    $ids = $user->invitations()->where('code', $forUser->code)->pluck('id');

    expect($ids->all())->toBe([$forUser->id]);
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

test('the upgrade migration converts the used schema', function () {
    Schema::dropIfExists('invitations');
    Schema::create('invitations', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuidMorphs('invitable');
        $table->uuid('author_id');
        $table->uuid('code');
        $table->json('data')->nullable();
        $table->boolean('used')->default(false);
        $table->timestamp('expires_at');
        $table->timestamps();
    });

    $id = Str::uuid();
    $updatedAt = now()->subDay()->startOfSecond();
    DB::table('invitations')->insert([
        'id' => $id,
        'invitable_type' => Invitable::class,
        'invitable_id' => (string) Str::uuid(),
        'author_id' => Str::uuid(),
        'code' => Str::uuid(),
        'used' => true,
        'expires_at' => now()->addDay(),
        'created_at' => now(),
        'updated_at' => $updatedAt,
    ]);

    $migration = require __DIR__.'/../database/migrations/alter_invitations_table_add_accepted_and_rejected_at.php';
    $migration->up();

    $invitation = Invitation::find($id);

    expect($invitation->accepted_at->eq($updatedAt))->toBeTrue()
        ->and($invitation->rejected_at)->toBeNull()
        ->and(Schema::hasColumn('invitations', 'used'))->toBeFalse();
});
