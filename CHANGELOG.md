# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/); versioning is
[Semantic Versioning](https://semver.org/), with the 0.x caveat that a minor
bump may carry breaking changes until the API stabilizes at 1.0.

Nothing before this point is tracked — the changelog starts here, at 0.12.0,
the first public release.

## [Unreleased]

### Added

- Forms: a reusable multi-step data-collection engine. Subclass `Form`, list
  `Text` and `Choice` steps, implement `onComplete()`, register it with
  `formRegistry()` (or `loadForms()`) and start it with `MyForm::start($ctx)`.
  Each step is a new message edited to carry its answer; Back / Next / Skip
  live on the reply keyboard, inline buttons page dynamic choices, and a
  summary step confirms before anything is written. Steps can be conditional
  (`when`), depend on each other (`dependsOn`, re-validation of later answers
  after a change), be skippable, validate with Laravel rules, and show an
  automatic Required/Optional marker plus a hint derived from the rules. Forms
  expire after 24h idle by default. Ships an `en` and `fa` `forms` lang file.
- `StateAnswer::keyboard()`: a state answer can supply the reply keyboard
  shown while a user is in its state, and `getAllowedFields()` is now called
  after the state's method and params are set so it can vary per step.
- `answerStateSummary()`, a short `type#method` label for a stored state.

### Changed

- **Breaking:** `bot_users.state` is now a `TEXT` column holding JSON
  (`{"t":type,"m":method,"p":params}`) instead of a 255-character
  `TYPE#method?query` string. Params keep their types (an `int` stays an
  `int`, where it used to come back as a string) and can nest, and non-ASCII
  text is no longer percent-encoded to three times its size. The migration
  converts every state in the old format, so users mid-flow at deploy time
  carry on; there is no decoder for the old format afterwards. Code that
  reads the raw state string must go through `decodeAnswerState()`.
  `encodeAnswerState()` keeps its signature.
- **Breaking:** `MessageMeta::continueAction()` and
  `updateAndContinueAction()` now edit the message in place instead of
  deleting it and sending a new one, so they keep working past the 48 hours
  after which Telegram refuses to delete a message. The message no longer
  moves to the bottom of the chat. `deleteMessage()` still deletes, but a
  message Telegram will no longer delete has its inline keyboard stripped
  instead of raising an error report.
- `TbeLogger` logs the state as `type#method` rather than the whole payload.
- `BotUser::addParamToState()` no longer writes a bogus state for a user with
  none.

### Fixed

- `TelegramResponse::update()` no longer throws when its edit fails while
  handling a text message (there is no callback query to answer).

## [0.12.0] - 2026-09-02

### Added

- Bot-user reachability tracking: a `bot_users.status`
  (`active`/`blocked`/`unreachable`) column, `telegram_users.deactivated_at`
  for the peer-global "account deleted" state, `BotUser::reachability()` and
  the `reachable()`/`withStatus()`/`deactivated()` query scopes, and a
  `BotUserStatus` service (`botUserStatus()`) that folds Telegram's
  `my_chat_member` push signal and send-failure errors into one persisted
  transition and one `BotUserStatusChanged` event.
- `Prunable` on `StateData`, `InlineConfirmation`, and `MessageMeta`,
  scheduled hourly via `model:prune`, replacing the ad-hoc cleanup query that
  ran on every `InlineConfirmation` accept/decline.
- `HandlerContextExpired` exception plus `StateAnswer::requireMessageMeta()` /
  `requireStateData()`: a multi-step flow that resumes against a `MessageMeta`
  or `StateData` row pruned out from under it (the user left it sitting past
  the retention window) now surfaces a "this step expired" notice and has its
  stuck state cleared, instead of dereferencing null and crashing the worker.
- A config flag and prefix to let the consuming app opt out of essence's own
  routes (`tbe-essence.routes`).
- `update_id` deduplication on the webhook: claimed atomically via
  `Cache::add` before processing and released on failure, so a crashed
  handler is retried but a slow-yet-successful one is never double-processed.
- `tbeApiResponse()` helper, plus the `nextInArray()` / `prevInArray()`
  array-cycling helpers, internalizing what was previously pulled in from
  `elyar/personal-laravel-helpers`. Both walk a list of values and wrap
  around the ends, so a "cycle to the next option" button never runs off
  the array.
- A Pest test suite (unit + feature), a Laravel Pint config, and a PHPStan
  config (level max, with a committed baseline).
- GitHub Actions CI: Pest across PHP 8.3/8.4/8.5 x Laravel 12/13, plus Pint
  and PHPStan.
- `ResolvesBotLocale` contract: essence binds a default implementation that
  returns `config('app.locale')`, and calls it both from its own
  `BotWebhookInitialized` listener and from `tbe:set-webhook`'s per-bot
  command-menu loop (which has no webhook request to hang a listener off
  of, and previously built every bot's command menu in whatever locale the
  app happened to be in). A companion package that owns real per-bot locale
  data rebinds the interface; essence never references that package.
- `TelegramBotEssentials\Essence\Testing\TestCase`: a reusable Testbench
  base every companion package can extend via its existing essence
  dependency, with `Http::fake()` on by default (Telegram API calls route
  through `Illuminate\Support\Facades\Http`, so this needs no bespoke SDK
  mocking), `makeBot()`/`makeMessageUpdate()`/`makeCallbackQueryUpdate()`
  factories, `postWebhookUpdate()` (a real request through routing,
  `TelegramBotAuthentication`, and the controller), and
  `assertTelegramSent()`.

### Changed

- **BREAKING:** `ResolvesBotLocale` replaces a companion package's own
  `BotWebhookInitialized` listener as the locale-setting mechanism for the
  webhook path - see the corresponding companion package's changelog.
- **BREAKING:** Handlers (`Command`, `ReplyKey`) resolve their label lazily
  now, via overridable `text()`/`response()`/`description()` methods that
  call `__()` directly, instead of translating once in the constructor. The
  buses that hold them are keyed by class name instead of by label. This
  makes registration correct under every locale a bot might be in, so
  handlers can be registered once per Octane worker instead of rescanned on
  every request. (Went through a `$textKey`-style string-property design
  first; reverted before release - a bare string property is invisible to
  IDE tooling, where a literal `__()` call gets autocomplete, navigation,
  and missing-key inspection.)
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
- `ExceptionHandler` no longer recurses into itself: its fallback
  notification path is now skipped unless `wHook()->check()` passes, so a
  stray exception with a half-populated (or absent) webhook context reports
  once and stops instead of re-dereferencing `wHook()` until the worker OOMs.
- `StateAnswer::stateData()` no longer reports an exception as a side effect
  of returning `null` - a missing row is the caller's decision to make (see
  `requireStateData()`).
- `BotFactory` now generates always-truthy, Telegram-shaped `bot_token` and
  `secret_token` values; `fake()->randomNumber()` could return `0`, and a
  falsy token made the SDK fall back to the unset `TELEGRAM_BOT_TOKEN` env
  var and throw.
- The `tbe:make:*` generators and `tbe:bot-management-token` now narrow
  `Command::argument()`/`option()` and `config()` values through `is_string()`
  rather than a bare cast, so they pass Larastan at level max.

### Removed

- The `elyar/personal-laravel-helpers` dependency, internalized as
  `ApiResponse`/`tbeApiResponse()` and the `nextInArray()` / `prevInArray()`
  helpers.
- A dead debug `/test` route and its commented-out code.
- The `inspire` console route scaffolding.
