# Laravel Invitations

Laravel package for time-limited invitations between any two models (a sender author and an invited invitable).

> ⚠️ This library is in active development so the API may change.

## Requirements

- PHP `^8.4` (8.4, 8.5, 8.6)
- Laravel `^13.0`

## Installation

```bash
composer require 26b/laravel-invitations
```

The service provider is auto-discovered by Laravel.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=invitations-config
```

### Configuration options

```php
return [
    'purge' => [
        // Delete invitations older than this many days.
        // Set to false to disable purging.
        'expiration_in_days' => 30,
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
- `invitable_type` / `invitable_id` polymorphic relation to the invited model
- `author_type` / `author_id` nullable polymorphic relation to the model that sent the invitation
- `code` UUID invitation code (unique)
- `data` free-form JSON payload for your application (message, metadata, ...)
- `accepted_at` / `rejected_at` nullable timestamps
- `expires_at` timestamp
- `expired_dispatched_at` nullable timestamp, set when `InvitationExpired` is sent
- `created_at` / `updated_at` timestamps

Run the migration:

```bash
php artisan migrate
```

### ⚠️ Upgrade notice

The previous `create_invitations_table` and `alter_invitations_table_add_accepted_and_rejected_at` migrations are replaced by a single `recreate_invitations_table` migration that **drops any existing `invitations` table and rebuilds it** from scratch.

1. Publish the updated migration:
   ```bash
   php artisan vendor:publish --tag=invitations-migrations
   ```
2. Run `php artisan migrate`.

Existing invitations are deleted. This is intentional: invitations created with the previous structure are time-limited and safe to discard. If your production data is invite-intensive, export or migrate the rows yourself before running the migration.

If you previously published the old migrations, you can delete their files from `database/migrations`; they are already recorded, so leaving them in place is harmless.

## Models

### `Invitation`

The `TwentySixB\LaravelInvitations\Models\Invitation` model provides:

- `accept()` — atomically marks the invitation accepted: throws `InvitationExpiredException` when past due, `InvitationAlreadyAcceptedException` / `InvitationAlreadyRejectedException` when already resolved, otherwise sets `accepted_at` and dispatches `InvitationAccepted`. Concurrent calls can only succeed once.
- `reject()` — same atomic guard, sets `rejected_at` and dispatches `InvitationRejected`.
- `expire()` — sets `expires_at` to one hour ago.
- `isExpired()` — unresolved and past due (consistent with `scopeExpired()`).
- `isAccepted()`, `isRejected()`, `isResolved()` — state checks.
- `scopeActive()` — unresolved and not expired.
- `scopeExpired()` — unresolved and past due (feeds the commands below).
- `scopeAccepted()` / `scopeRejected()` — resolved invitations.
- `invitable()` — polymorphic relation to the model the invitation is addressed to.
- `author()` — polymorphic relation to the model that sent the invitation (nullable).

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

## Handling accept/reject

The package is logic-only — your application decides the HTTP/UX shape:

```php
try {
    $invitation->accept();
    return redirect()->route('home');
} catch (InvitationAlreadyAcceptedException) {
    // already used
} catch (InvitationAlreadyRejectedException) {
    // already declined
} catch (InvitationExpiredException) {
    // too late
}
```

## Authorization

The package registers an `InvitationPolicy` that controls who can view, delete, and create invitations:

- `view` — allowed for the invited model (the `invitable`) and for the author.
- `delete` — allowed for the invited model and for the author.
- `create` — allowed for everyone by default.

Both checks are structural morph comparisons (`invitable_type` / `invitable_id` and `author_type` / `author_id`); the package does not read `data`.

## Events

The package dispatches events you can listen to in your application:

| Event                 | Dispatched when                                        | Payload accessor |
| --------------------- | ------------------------------------------------------ | ---------------- |
| `InvitationAccepted`  | `accept()` succeeds.                                   | `getInvitation()` |
| `InvitationRejected`  | `reject()` succeeds.                                   | `getInvitation()` |
| `InvitationExpired`   | `invitations:dispatch-expired` runs for an expired invitation. | `getInvitation()` |

Create listeners with `php artisan make:listener` and register them in your `EventServiceProvider`.

> `InvitationExpired` is sent once per invitation: `invitations:dispatch-expired` stamps `expired_dispatched_at` on each row it reports, so repeated runs do not re-send. Make listeners idempotent anyway, since the reported stamp is written after the dispatch.

## Console commands

```bash
php artisan invitations:purge               # delete stale invitations (retention)
php artisan invitations:dispatch-expired    # dispatch InvitationExpired for expired invitations
```

`invitations:purge` deletes expired (unresolved and past `expires_at`) invitations whose `expires_at` is older than `invitations.purge.expiration_in_days`. Set `expiration_in_days` to `false` to disable purging.

Target other states with `--accepted`, `--rejected`, or `--all` (any state). Override the age cutoff per run with `--days=`, e.g. `invitations:purge --accepted --days=7`. Combinations like `--accepted` plus `--rejected` purge either state. Use `--force` to also purge expired invitations that the dispatch job has not reported yet (i.e. skip the reported check).

`invitations:dispatch-expired` sends `InvitationExpired` for each expired invitation that was not reported yet (stamps `expired_dispatched_at` on every row it reports, so it is safe to run repeatedly), batching 50 oldest-first per run. The default purge keeps any expired invitation that the dispatch job has not yet reported, so the two commands can be scheduled in any order.

Schedule the commands in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('invitations:dispatch-expired')->daily();
Schedule::command('invitations:purge')->weekly();
```

## Factory

Use the factory with the invited model and, optionally, the sender:

```php
use TwentySixB\LaravelInvitations\Models\Invitation;

$invitation = Invitation::factory()
    ->forInvitable($invitee)
    ->from($sender)
    ->create();
```

State modifiers: `pending()`, `expired()`, `accepted()`, `rejected()`.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).