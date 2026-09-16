# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

## [0.0.1] - 2026-09-16

### Added

- Laravel 13 support.
- Support for PHP 8.4, 8.5, and 8.6.
- `InvitationExpired` and `InvitationRejected` events.
- `InvitationAlreadyAcceptedException` and `InvitationAlreadyRejectedException`.
- `accept()` and `reject()` lifecycle methods on the `Invitation` model.
- `isAccepted()`, `isRejected()`, `isResolved()`, `scopeAccepted()`, `scopeRejected()`.
- `invitations:dispatch-expired` console command.
- Single `recreate_invitations_table` migration that drops and rebuilds the `invitations` table with the new structure (previous invitations are discarded — see the upgrade notice in the README).
- `author()` polymorphic relation on the `Invitation` model and a `from()` factory state for setting the sender.
- Test suite powered by [Pest](https://pestphp.com) v5.
- [Laravel Pint](https://laravel.com/docs/pint) and [Larastan](https://larastan.com) as dev tooling (level 5 analysis, configured in `phpstan.neon.dist`).
- GitHub Actions workflow: Pint auto-commit job plus Pest and Larastan jobs across a PHP 8.4/8.5/8.6 matrix.

### Changed

- Rewritten as a logic-only package: no controllers, views, or Livewire components; lifecycle is handled via model methods, events, and exceptions.
- Dropped PHP 8.3 support (`pestphp/pest` v5 requires PHP 8.4).
- `used` boolean column replaced by an `accepted_at` nullable timestamp.
- `code` column now has a unique index.
- `invitations:purge` now only removes unresolved expired invitations by default, with `--accepted`, `--rejected`, and `--all` flags for other states and a `--days=` argument to override the age cutoff. A `--force` flag skips the reported check.
- `invitations:dispatch-expired` now stamps `expired_dispatched_at` on each invitation it reports, making the command idempotent across runs; purge leaves unreported expired invitations alone until they are reported.
- `accept()` and `reject()` now take effect atomically via a conditional update, so concurrent calls to the same invitation can only resolve it once.
- `reject()` now persists a `rejected_at` timestamp instead of deleting the invitation.
- `scopeActive()` / `scopeExpired()` predicates fixed and now exclude resolved invitations.
- `isExpired()` now excludes resolved invitations, matching `scopeExpired()`.
- `invitable` now refers to the invited (recipient) model; `author` is a nullable polymorphic sender. `data` is a free-form application payload the package no longer reads.
- `HasInvitations::invitations()` is now a plain `morphMany` on `invitable` (no more `data` JSON matching).
- `InvitationPolicy` authorization is now structural morph comparison: `view` and `delete` allow the invited model and the author.
- `InvitationExpiredException` no longer renders an HTTP view.
- `InvitationPolicy` is now registered via the gate; dead `$policies` property removed.
- Factory no longer depends on the removed `invitables` config; invitable is attached via `forInvitable()`. `unused()` renamed `pending()`, plus `accepted()` and `rejected()` states; `expired()` now produces a past `expires_at`.
- Updated `spatie/laravel-package-tools` to `^1.93`.
- Updated `orchestra/testbench` to `^11.0`.

### Removed

- `livewire/livewire` and `chillerlan/php-qrcode` dependencies.
- Livewire components (`Viewer`, `Lister`, `InviteUsers`), invitational controller, QR code view component, and all package views.
- `Actions` service layer (`Accept`, `Reject`, `Expired`, `Delete`, `Filter`).
- `InviteCodeUsed`, `UserInvited`, and `InviteByEmail` events.
- `InvalidCodeException` and `AccessDeniedException`.
- `invitables`, `actions`, and `fallback_route` configuration keys.
- `invitations.models` configuration group (`user` and `invitation` keys) and the `author_id` foreign key to `users.id` (`author_id` is kept as a plain morph column). The `Invitation` model is no longer configurable.
- `create_invitations_table` and `alter_invitations_table_add_accepted_and_rejected_at` migrations, replaced by the single `recreate_invitations_table`.
- `composer.lock` from version control.
