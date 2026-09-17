# Laravel Invitations

Laravel package for time-limited invitations between any two models (a sender and a recipient).

> ⚠️ This library is in active development so the API may change.

## Requirements

- PHP `^8.4` (tested on 8.4 and 8.5)
- Laravel `^13.0`

## Installation

```bash
composer require 26b/laravel-invitations
```

The service provider is auto-discovered by Laravel.

## Configuration

The package works with sensible defaults; publish the configuration file only if you need to change them:

```bash
php artisan vendor:publish --tag=invitations-config
```

### Configuration options

```php
return [
    'purge' => [
        // Delete invitations older than this many days.
        // Set to false to disable purging.
        'expired_days' => 30,
    ],
];
```

## Database

Publish the migrations:

```bash
php artisan vendor:publish --tag=invitations-migrations
```

The migration creates an `invitations` table with:

- `id` UUID primary key
- `recipient_type` / `recipient_id` polymorphic relation to the model the invitation is addressed to
- `sender_type` / `sender_id` nullable polymorphic relation to the model that sent the invitation
- `code` UUID invitation code (unique)
- `data` free-form JSON column for your application (message, metadata, ...); the package never reads it
- `accepted_at` / `rejected_at` nullable timestamps
- `expires_at` timestamp; the invitation is expired once it is reached
- `expired_dispatched_at` nullable timestamp, stamped when `InvitationExpired` is dispatched
- `created_at` / `updated_at` timestamps

Run the migration:

```bash
php artisan migrate
```

### ⚠️ Upgrade notice

The `recreate_invitations_table` migration **drops any existing `invitations` table and rebuilds it** from scratch, so re-running it always yields the current schema:

1. Publish the migration (add `--force` if you published it before):
   ```bash
   php artisan vendor:publish --tag=invitations-migrations --force
   ```
2. Re-run it:
   ```bash
   php artisan migrate:rollback --step=1 && php artisan migrate
   ```

Existing invitations are deleted. This is intentional: invitations are time-limited and safe to discard. If your production data is invite-intensive, export or migrate the rows yourself before running the migration.

The pre-`0.0.1` `create_invitations_table` and `alter_invitations_table_add_accepted_and_rejected_at` migrations are replaced by this single migration. If you published those, you can delete their files from `database/migrations`; they are already recorded, so leaving them in place is harmless.

## Models

### `Invitation`

The `TwentySixB\LaravelInvitations\Models\Invitation` model provides:

- `accept()` — atomically marks the invitation accepted: throws `InvitationAlreadyExpiredException` when past due, `InvitationAlreadyAcceptedException` / `InvitationAlreadyRejectedException` when already resolved, otherwise sets `accepted_at` and dispatches `InvitationAccepted`. Concurrent calls can only succeed once.
- `reject()` — same atomic guard, sets `rejected_at` and dispatches `InvitationRejected`.
- `expire()` — expires the invitation immediately, ahead of its `expires_at`: it sets `expires_at` and `expired_dispatched_at` to now, persists, and dispatches `InvitationExpired` (so `invitations:dispatch-expired` will not dispatch it again). Already accepted or rejected invitations are left untouched.
- `isExpired()` — unresolved and at or past `expires_at` (consistent with `scopeExpired()`).
- `isPending()` — unresolved and before `expires_at` (consistent with `scopePending()`).
- `isAccepted()`, `isRejected()`, `isResolved()` — state checks.
- `scopePending()` — unresolved and not expired.
- `scopeExpired()` — unresolved and past due (feeds the commands below).
- `scopeAccepted()` / `scopeRejected()` — resolved invitations.
- `recipient()` — polymorphic relation to the model the invitation is addressed to.
- `sender()` — polymorphic relation to the model that sent the invitation (nullable).

### `HasInvitations` trait

Add the `TwentySixB\LaravelInvitations\Models\Concerns\HasInvitations` trait to a model you address invitations to (usually your `User`) to query its invitations:

```php
use TwentySixB\LaravelInvitations\Models\Concerns\HasInvitations;

class User extends Authenticatable
{
    use HasInvitations;
}
```

Then access invitations with:

```php
$user->invitations()->get();
```

The relation covers the recipient side only. To list the invitations a model sent, query the `sender` morph directly:

```php
Invitation::where('sender_type', $model->getMorphClass())
    ->where('sender_id', $model->getKey())
    ->get();
```

## Creating an invitation

The package stores invitations but does not create them for you. Create one with the model, then share its `code` with the recipient:

```php
use Illuminate\Support\Str;
use TwentySixB\LaravelInvitations\Models\Invitation;

$invitation = Invitation::create([
    'code'           => (string) Str::uuid(),
    'sender_type'    => $sender->getMorphClass(),
    'sender_id'      => $sender->getKey(),
    'recipient_type' => $recipient->getMorphClass(),
    'recipient_id'   => $recipient->getKey(),
    'expires_at'     => now()->addDays(7),
    'data'           => ['message' => 'Join my team'],
]);
```

- `code` must be unique; the package does not generate it.
- `recipient_*` point at the model the invitation is addressed to, `sender_*` at the model that sends it. The sender is optional — omit both `sender_*` columns to record no sender.
- `data` is a free-form column the package never reads.
- Both models must be persisted so their morph keys exist.

## Handling accept/reject

The package is logic-only — your application decides the HTTP/UX shape. Look the invitation up by `code`, then call `accept()` or `reject()`:

```php
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyAcceptedException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyExpiredException;
use TwentySixB\LaravelInvitations\Exceptions\InvitationAlreadyRejectedException;
use TwentySixB\LaravelInvitations\Models\Invitation;

$invitation = Invitation::where('code', $request->string('code'))->firstOrFail();

try {
    $invitation->accept();

    return redirect()->route('home');
} catch (InvitationAlreadyAcceptedException $e) {
    // already used
} catch (InvitationAlreadyRejectedException $e) {
    // already declined
} catch (InvitationAlreadyExpiredException $e) {
    // too late
}
```

Each exception reports the moment the state was reached, both in its message and as a public readonly property (`$e->acceptedAt`, `$e->rejectedAt`, `$e->expiredAt`).

## Authorization

The package registers an `InvitationPolicy` that controls who can view, delete, and create invitations:

- `view` — allowed for the recipient and for the sender.
- `delete` — allowed for the recipient and for the sender.
- `create` — allowed for everyone by default.

All other abilities (`viewAny`, `update`, `restore`, `forceDelete`) are denied.

Both checks are structural morph comparisons (`recipient_type` / `recipient_id` and `sender_type` / `sender_id`); the package does not read `data`.

### Extending the policy

Subclass `InvitationPolicy` and override its `before()` hook to add rules without reimplementing the defaults. `before()` runs for every ability the policy handles and returns `true` to allow, `false` to deny, or `null` to fall through to the built-in checks (`isRecipient()` / `isSender()`, available to subclasses):

```php
use Illuminate\Database\Eloquent\Model;
use TwentySixB\LaravelInvitations\Models\Invitation;
use TwentySixB\LaravelInvitations\Policies\InvitationPolicy;

class AppInvitationPolicy extends InvitationPolicy
{
    public function before(Model $user, string $ability, mixed ...$arguments): ?bool
    {
        $invitation = $arguments[0] ?? null;

        if ($invitation instanceof Invitation && $user->hasRole('admin') && ! $invitation->isResolved()) {
            return true;
        }

        return null;
    }
}
```

Register the subclass from a service provider that boots after the package's (package providers boot before your application's):

```php
use Illuminate\Support\Facades\Gate;
use TwentySixB\LaravelInvitations\Models\Invitation;

Gate::policy(Invitation::class, AppInvitationPolicy::class);
```

`$arguments` holds what the ability was checked with: an `Invitation` for `view`/`delete`, and the class string for `create`/`viewAny`, so treat it as `mixed`.

## Events

The package dispatches events you can listen to in your application:

| Event                 | Dispatched when                                        | Payload accessor |
| --------------------- | ------------------------------------------------------ | ---------------- |
| `InvitationCreated`   | an invitation is created.                              | `getInvitation()` |
| `InvitationAccepted`  | `accept()` succeeds.                                   | `getInvitation()` |
| `InvitationRejected`  | `reject()` succeeds.                                   | `getInvitation()` |
| `InvitationExpired`   | `expire()` is called, or `invitations:dispatch-expired` runs for an expired invitation. | `getInvitation()` |

Create listeners with `php artisan make:listener` and register them in your `EventServiceProvider`.

> `InvitationCreated` fires on every creation path, including factories and seeders. This is the hook for sending your own notifications — the package does not define channels or notification classes.
>
> It is dispatched from Eloquent's `created` event, so in tests use `Event::fake([InvitationCreated::class])` — a blanket `Event::fake()` also fakes `eloquent.created` and stops this event from firing.

> `InvitationExpired` is dispatched once per invitation: both `expire()` and `invitations:dispatch-expired` stamp `expired_dispatched_at`, so repeated runs do not re-dispatch. Make listeners idempotent anyway, since the stamp is written after the dispatch.

## Console commands

```bash
php artisan invitations:purge               # delete stale invitations (retention)
php artisan invitations:dispatch-expired    # dispatch InvitationExpired for expired invitations
```

`invitations:purge` deletes expired (unresolved and at or past `expires_at`) invitations whose `expires_at` is older than `invitations.purge.expired_days`. Set `expired_days` to `false` to disable purging.

Target other states with `--accepted`, `--rejected`, or `--all` (any state). Override the age cutoff per run with `--days=`, e.g. `invitations:purge --accepted --days=7`. Combinations like `--accepted` plus `--rejected` purge either state. Use `--force` to also purge expired invitations whose expiry has not been dispatched yet (i.e. skip the dispatch check).

The `--days` cutoff applies to every mode, and each run processes at most 50 invitations (oldest expiry first) — run or schedule it again to clear a larger backlog.

`invitations:dispatch-expired` dispatches `InvitationExpired` for each expired invitation whose expiry was not dispatched yet (it stamps `expired_dispatched_at` on every row it dispatches, so it is safe to run repeatedly), batching 50 oldest-first per run. The default purge keeps any expired invitation that has not been dispatched yet, so the two commands can be scheduled in any order.

Schedule the commands in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('invitations:dispatch-expired')->daily();
Schedule::command('invitations:purge')->weekly();
```

## Factory

Use the factory when seeding or writing tests, with the recipient and, optionally, the sender:

```php
use TwentySixB\LaravelInvitations\Models\Invitation;

$invitation = Invitation::factory()
    ->forRecipient($recipient)
    ->fromSender($sender)
    ->create();
```

State modifiers: `pending()`, `expired()`, `accepted()`, `rejected()`.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).