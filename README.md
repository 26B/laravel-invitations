# Laravel Invitations

Laravel package to invite users to models via invitations with QR codes and Livewire components.

> ⚠️ This library is in active development so the API may change.

## Requirements

- PHP `^8.3` (8.3, 8.4, 8.5)
- Laravel `^13.0`
- Livewire `^3.5`

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
    // Models that can receive invitations (used by the factory and filters).
    'invitables' => [
        // \App\Models\Event::class,
    ],

    // Action classes that handle invitation lifecycle.
    // Replace these with your own implementations if you need custom behavior.
    'actions' => [
        'accept'  => TwentySixB\LaravelInvitations\Actions\Accept::class,
        'expired' => TwentySixB\LaravelInvitations\Actions\Expired::class,
        'reject'  => TwentySixB\LaravelInvitations\Actions\Reject::class,
        'delete'  => TwentySixB\LaravelInvitations\Actions\Delete::class,
        'filter'  => TwentySixB\LaravelInvitations\Actions\Filter::class,
    ],

    // Route name used when no explicit redirect is set.
    'fallback_route' => 'dashboard',

    'purge' => [
        // Delete invitations older than this many days.
        // Set to false to disable purging.
        'expiration_in_days' => 30,
    ],

    'models' => [
        // Your application's User model.
        'user'       => \App\Models\User::class,

        // Invitation model used by the package.
        'invitation' => \TwentySixB\LaravelInvitations\Models\Invitation::class,
    ],
];
```

## Database

Publish the migration:

```bash
php artisan vendor:publish --tag=invitations-migrations
```

The migration creates an `invitations` table with:

- `id` UUID primary key
- `invitable_type` / `invitable_id` polymorphic relation
- `author_id` foreign key to `users.id`
- `code` UUID invitation code
- `data` JSON payload (email, user info, redirect, etc.)
- `used` boolean flag
- `expires_at` timestamp
- `created_at` / `updated_at` timestamps

Run the migration:

```bash
php artisan migrate
```

## Models

### `Invitation`

The `TwentySixB\LaravelInvitations\Models\Invitation` model provides:

- `use()` — marks the invitation as used and saves it. Throws `InvitationExpiredException` if expired.
- `expire()` — sets the expiration to one hour ago.
- `isExpired()` — checks `expires_at` against the current time.
- `invitable()` — polymorphic relation to the invited model.
- `author()` — belongs-to relation to the configured user model.
- `scopeActive()` / `scopeExpired()` — query scopes.

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

## Authorization

The package registers an `InvitationPolicy` that controls who can view, delete, and create invitations:

- `view` — allowed if the user's email or ID matches `data.email` / `data.user.id`.
- `delete` — allowed if the user can view the invitation or if the user is the author.
- `create` — allowed for everyone by default.

## Livewire components

The package registers two Livewire components:

| Component name           | Class                                                          | Purpose                                          |
| ------------------------ | -------------------------------------------------------------- | ------------------------------------------------ |
| `invitations.viewer`     | `TwentySixB\LaravelInvitations\Livewire\Viewer`                | Display and accept/reject a single invitation.   |
| `invitations.lister`     | `TwentySixB\LaravelInvitations\Livewire\Lister`                | Display a list of invitations for a user/model.  |

Use them in your Blade views:

```blade
<livewire:invitations.viewer :invitation_id="$invitation->id" />

<livewire:invitations.lister :target="$event" mode="received" />
```

### Required application views

The Livewire components look for the following views in your application, not in the package:

- `resources/views/livewire/invitations/viewer.blade.php`
- `resources/views/livewire/invitations/viewer-invitation-missing.blade.php`
- `resources/views/livewire/invitations/list.blade.php`

You must create these views yourself. The components pass `$invitation` and `$invitations` to the views as needed.

## QR code component

Render a QR code for any URL:

```blade
<x-invitations::code route="{{ route('events.show', $event) }}?code={{ $invitation->code }}" />
```

The component renders a base64 data URI inside an `<img>` tag.

If you want to use the included `InvitationController::validateCode`, register the route manually in your application:

```php
use Illuminate\Support\Facades\Route;
use TwentySixB\LaravelInvitations\Http\Controllers\InvitationController;

Route::get('/qrcode/scanned/{model}/{id}/{code}', [InvitationController::class, 'validateCode'])
    ->name('invite.qrcode.scanned');
```

The controller expects the target model to expose an `invite_code` attribute that matches the scanned code. When validation succeeds it dispatches `InviteCodeUsed`.

> Note: `chillerlan/php-qrcode` v4 returns a PNG data URI by default, while v5 returns an SVG data URI. Both work in the `<img>` tag. Pin `outputType` in `QROptions` if you need a fixed format.

## Actions

Actions are plain static classes resolved from the `invitations.actions` config. You can replace them with custom implementations.

| Action    | Purpose                                                                 |
| --------- | ----------------------------------------------------------------------- |
| `Accept`  | Marks an invitation as used, dispatches `InvitationAccepted`, redirects.|
| `Reject`  | Deletes the invitation and redirects.                                   |
| `Expired` | Redirects when an invitation has expired.                               |
| `Delete`  | Deletes an invitation after checking the `delete` gate.                 |
| `Filter`  | Returns a collection of invitations for the current user or target models.|

## Events

The package dispatches events you can listen to in your application:

| Event                | Dispatched when                                      | Payload accessors                          |
| -------------------- | ---------------------------------------------------- | ------------------------------------------ |
| `InvitationAccepted` | An invitation is accepted.                           | `getInvitation()`                          |
| `InviteCodeUsed`     | A QR code is scanned and validated.                  | `getModel()`                               |
| `UserInvited`        | An existing user is invited.                         | `getUser()`, `getTarget()`, `getInviter()` |
| `InviteByEmail`      | A non-existent user is invited by email.             | `getEmail()`, `getTarget()`, `getInviter()` |

Create listeners with `php artisan make:listener` and register them in your `EventServiceProvider`.

## Console command

Purge stale invitations:

```bash
php artisan invitations:purge
```

The command deletes invitations whose `expires_at` is older than `invitations.purge.expiration_in_days`. Set `expiration_in_days` to `false` to disable purging.

Schedule the command in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('invitations:purge')->daily();
```

## Views

Publish the package views so you can customize error pages and the QR code component:

```bash
php artisan vendor:publish --tag=invitations-views
```

Published views land in `resources/views/vendor/invitations/`.

The package ships with:

- `errors/access-denied.blade.php`
- `errors/expired.blade.php`
- `errors/invalid-code.blade.php`
- `components/code.blade.php`

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
