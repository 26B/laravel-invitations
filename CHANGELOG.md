# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

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
