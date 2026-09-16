# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Laravel 13 support.
- Support for PHP 8.3, 8.4, and 8.5.
- Livewire 3 support.
- `livewire/livewire` added as an explicit requirement.

### Changed

- Updated `spatie/laravel-package-tools` to `^1.93`.
- Updated `orchestra/testbench` to `^11.0`.
- `chillerlan/php-qrcode` constraint widened to `^4.3|^5.0`.
- Action handlers now return `Illuminate\Http\RedirectResponse` for Livewire 3 compatibility.

### Removed

- Removed `composer.lock` from version control.

[Unreleased]: https://github.com/26b/laravel-invitations/compare/HEAD...HEAD
