# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Laravel 13 support.
- Support for PHP 8.3, 8.4, and 8.5.
- `InvitationExpired` and `InvitationRejected` events.
- `InvitationAlreadyAcceptedException` and `InvitationAlreadyRejectedException`.
- `accept()` and `reject()` lifecycle methods on the `Invitation` model.
- `isAccepted()`, `isRejected()`, `isResolved()`, `scopeAccepted()`, `scopeRejected()`.
- `invitations:dispatch-expired` console command.
- Upgrade migration converting the `used` boolean to `accepted_at` (backfilled from `updated_at`) and adding `rejected_at`.
- Test suite powered by [Pest](https://pestphp.com) v5.

### Changed

- Rewritten as a logic-only package: no controllers, views, or Livewire components; lifecycle is handled via model methods, events, and exceptions.
- `used` boolean column replaced by an `accepted_at` nullable timestamp.
- `reject()` now persists a `rejected_at` timestamp instead of deleting the invitation.
- `scopeActive()` / `scopeExpired()` predicates fixed and now exclude resolved invitations.
- `HasInvitations::invitations()` groups its OR clause so chained constraints apply to both branches.
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
- `composer.lock` from version control.

[Unreleased]: https://github.com/26b/laravel-invitations/compare/HEAD...HEAD