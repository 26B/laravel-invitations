# Laravel Invitations

Laravel package to invite users via time-limited invitations for any invitable model.

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

    'models' => [
        // Your application's User model.
        'user' => \App\Models\User::class,

        // Invitation model used by the package.
        'invitation' => \TwentySixB\LaravelInvitations\Models\Invitation::class,
    ],
];
```

## Database

Publish the migrations:

```bash
php artisan vendor:publish --tag=invitations-migrations
```

The migrations create an `invitations` table with:

- `id` UUID primary key
- `invitable_type` / `invitable_id` polymorphic relation
- `author_id` foreign key to `users.id`
- `code` UUID invitation code
- `data` JSON payload (email, user info, etc.)
- `accepted_at` / `rejected_at` nullable timestamps
- `expires_at` timestamp
- `created_at` / `updated_at` timestamps

For installs that already ran the previous version, an upgrade migration converts the old `used` boolean into `accepted_at` (backfilled from `updated_at` for already-accepted invitations) and adds `rejected_at`. New installs get the final schema directly.

Run the migration:

```bash
php artisan migrate
```

## Models

### `Invitation`

The `TwentySixB\LaravelInvitations\Models\Invitation` model provides:

- `accept()` — throws `InvitationExpiredException` when past due, `InvitationAlreadyAcceptedException` / `InvitationAlreadyRejectedException` when already resolved, otherwise sets `accepted_at` and dispatches `InvitationAccepted`.
- `reject()` — same guards, sets `rejected_at` and dispatches `InvitationRejected`.
- `expire()` — sets `expires_at` to one hour ago.
- `isExpired()`, `isAccepted()`, `isRejected()`, `isResolved()` — state checks.
- `scopeActive()` — unresolved and not expired.
- `scopeExpired()` — unresolved and past due (feeds the commands below).
- `scopeAccepted()` / `scopeRejected()` — resolved invitations.
- `invitable()` — polymorphic relation to the invited model.
- `author()` — belongs-to relation to the configured user model.

### `HasInvitations` trait

Add the `TwentySixB\LaravelInvitations\Models\Concerns\HasInvitations` trait to your `User` model to query invitations addressed to the user:

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

- `view` — allowed if the user's email or ID matches `data.email` / `data.user.id`.
- `delete` — allowed if the user can view the invitation or if the user is the author.
- `create` — allowed for everyone by default.

## Events

The package dispatches events you can listen to in your application:

| Event                 | Dispatched when                                        | Payload accessor |
| --------------------- | ------------------------------------------------------ | ---------------- |
| `InvitationAccepted`  | `accept()` succeeds.                                   | `getInvitation()` |
| `InvitationRejected`  | `reject()` succeeds.                                   | `getInvitation()` |
| `InvitationExpired`   | `invitations:dispatch-expired` runs for an expired invitation. | `getInvitation()` |

Create listeners with `php artisan make:listener` and register them in your `EventServiceProvider`.

> `InvitationExpired` is at-least-once delivery: it fires on every run of `invitations:dispatch-expired` for still-unsent invitations. Make listeners idempotent.

## Console commands

```bash
php artisan invitations:purge               # delete stale invitations (retention)
php artisan invitations:dispatch-expired    # dispatch InvitationExpired for expired invitations
```

`invitations:purge` deletes invitations whose `expires_at` is older than `invitations.purge.expiration_in_days`. Set `expiration_in_days` to `false` to disable purging.

Schedule the commands in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('invitations:dispatch-expired')->daily();
Schedule::command('invitations:purge')->weekly();
```

## Factory

Use the factory with an invitable model:

```php
use TwentySixB\LaravelInvitations\Models\Invitation;

$invitation = Invitation::factory()->forInvitable($event)->create();
```

State modifiers: `pending()`, `expired()`, `accepted()`, `rejected()`.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).