# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-09-16

### Added

- `InvitationPolicy::before()` extension hook (returns `null` by default), so an application can widen or tighten access by subclassing the policy; the `isRecipient()`, `isSender()`, and `matches()` helpers are now `protected` for reuse.

## [0.1.0] - 2026-09-16

### Added

- `InvitationCreated` event, dispatched whenever an invitation is created, as the hook for sending application-defined notifications.
- `Invitation::isPending()` state check, consistent with `scopePending()`.

### Changed

**Breaking** — the following rename public classes, methods, config keys, and database columns. The `invitations` table must be rebuilt (see the upgrade notice in the README).

- Renamed the invitation parties to `sender` and `recipient` (previously `author` and `invitable`). This affects:
  - the `sender_type` / `sender_id` and `recipient_type` / `recipient_id` columns;
  - the `Invitation::sender()` and `Invitation::recipient()` relations;
  - the factory states `fromSender()` and `forRecipient()` (previously `from()` and `forInvitable()`);
  - the `InvitationPolicy` checks and the `HasInvitations` morph name.
- Renamed the `active` state to `pending`: `scopeActive()` is now `scopePending()`.
- Renamed `InvitationExpiredException` to `InvitationAlreadyExpiredException` (message now reads "already expired").
- Renamed the `PurgeExpiredInvitations` command class to `PurgeInvitations` (the `invitations:purge` signature is unchanged).
- Renamed the `purge.expiration_in_days` config key to `purge.expired_days`.
- `Invitation::expire()` now expires the invitation immediately: it sets `expires_at` to now, persists the model, stamps `expired_dispatched_at`, and dispatches `InvitationExpired`. It previously only set `expires_at` in memory. Invitations that are already accepted or rejected are left untouched.

### Fixed

- An invitation is now considered expired at `expires_at` itself (previously only strictly after it), so `isExpired()` and `scopeExpired()` agree with the stored timestamp and `expire()` takes effect immediately.
- Console command descriptions, `--force` help, and documentation now consistently say "dispatch" instead of "report".

### Removed

- Stale TODOs in the `recreate_invitations_table` migration and the unused `TestCase::invitable()` helper.

## [0.0.2] - 2026-09-16

### Fixed

- Removed PHP 8.6 from the test matrix: PHP 8.6 has not been released yet and `brianium/paratest` (via Pest 5) only supports PHP 8.4 and 8.5.

## [0.0.1] - 2026-09-16

Initial release.

### Added

- Time-limited invitations between any two Eloquent models: a polymorphic `invitable` (the recipient) and a nullable polymorphic `author` (the sender).
- `Invitation` lifecycle methods `accept()`, `reject()`, and `expire()`, with `isAccepted()`, `isRejected()`, `isResolved()`, and `isExpired()` state checks.
- Atomic accept/reject: a conditional update guarantees an invitation can only be resolved once, even under concurrent calls.
- Query scopes `active()`, `expired()`, `accepted()`, and `rejected()`.
- Events `InvitationAccepted`, `InvitationRejected`, and `InvitationExpired`.
- Exceptions `InvitationExpiredException`, `InvitationAlreadyAcceptedException`, and `InvitationAlreadyRejectedException`, each reporting the timestamp at which the state was reached.
- `HasInvitations` trait for querying the invitations addressed to a model.
- `InvitationPolicy`, registered with the gate, granting `view` and `delete` to the recipient and the author.
- `invitations:purge` command (`--accepted`, `--rejected`, `--all`, `--days=`, `--force`) driven by the `purge.expiration_in_days` config.
- `invitations:dispatch-expired` command that reports each expired invitation once.
- `recreate_invitations_table` migration and an `InvitationFactory` with `forInvitable()`, `from()`, and the `pending()`, `expired()`, `accepted()`, and `rejected()` states.
- Configuration and migrations published via the `invitations-config` and `invitations-migrations` tags.
- Logic-only design: lifecycle is handled through model methods, events, and exceptions, leaving controllers, views, and HTTP handling to the application.
- Laravel 13 support, on PHP 8.4 and 8.5.
- Pest 5 test suite, with Pint and Larastan (level 5) in GitHub Actions.
