# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/); versioning is
[Semantic Versioning](https://semver.org/), with the 0.x caveat that a minor
bump may carry breaking changes until the API stabilizes at 1.0.

Nothing before this point is tracked — the changelog starts here, at what
will ship as 0.12.0.

## [Unreleased]

### Added

- `Prunable` on `StateData`, `InlineConfirmation`, and `MessageMeta`,
  scheduled hourly via `model:prune`, replacing the ad-hoc cleanup query that
  ran on every `InlineConfirmation` accept/decline.
- A config flag and prefix to let the consuming app opt out of essence's own
  routes (`tbe-essence.routes`).
- `update_id` deduplication on the webhook: claimed atomically via
  `Cache::add` before processing and released on failure, so a crashed
  handler is retried but a slow-yet-successful one is never double-processed.
- `tbeApiResponse()` helper, internalizing the `apiResponse()` helper
  previously pulled in from `elyar/personal-laravel-helpers`.
- A Pest test suite (unit + feature), a Laravel Pint config, and a PHPStan
  config (level max, with a committed baseline).
- GitHub Actions CI: Pest across PHP 8.3/8.4/8.5 x Laravel 12/13, plus Pint
  and PHPStan.

### Changed

- **BREAKING:** Handlers (`Command`, `ReplyKey`) now hold translation keys
  (`$descriptionKey`, `$textKey`, `$responseKey`) instead of translated
  strings resolved once in the constructor, and the buses that hold them are
  keyed by class name instead of by label. This makes registration correct
  under every locale a bot might be in, so handlers can be registered once
  per Octane worker instead of rescanned on every request.
- **BREAKING:** `encodeCallback()` now throws instead of silently reordering
  or dropping later params when one is `null`/non-scalar, and enforces
  Telegram's 64-byte callback-data limit instead of shipping a dead button.
- Handler registration moved out of `TelegramWebhookController` and into
  `TelegramBotServiceProvider`: essence's built-ins register in `boot()`,
  and the consuming app's `app/Telegram/**` registers afterwards in
  `booted()` - so app handlers now win any collision with essence or a
  companion package. Previously essence was scanned last and silently won.
- Relicensed MIT (was GPL-3.0).
- Raised the minimum PHP version to 8.3, matching what Laravel 13 itself
  requires.

### Fixed

- `BotFactory`/`BotUserFactory` no longer write `currency`/`balance`
  columns that don't exist on any migration.
- `ExceptionHandler` no longer hard-depends on Telescope being installed.

### Removed

- The `elyar/personal-laravel-helpers` dependency, internalized as
  `ApiResponse`/`tbeApiResponse()`.
- A dead debug `/test` route and its commented-out code.
- The `inspire` console route scaffolding.
