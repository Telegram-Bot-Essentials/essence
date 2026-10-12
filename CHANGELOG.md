# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/); versioning is
[Semantic Versioning](https://semver.org/), with the 0.x caveat that a minor
bump may carry breaking changes until the API stabilizes at 1.0.

Nothing before this point is tracked — the changelog starts here, at 0.12.0,
the first public release.

## [Unreleased]

### Fixed

- Resolving a conversation reads before it inserts. It used to attempt an
  insert on every update and fall back to a read on the unique-key
  violation, which wasted a query and, on MySQL, an auto-increment id per
  update.

## [0.18.0] - 2026-10-12

State and the reply menu move from the user to the conversation: one user
in one chat and forum topic. This release is breaking: every
`$user->state` / `changeState()` / `addParamToState()` call moves to a
conversation (see **Upgrading**). Group chats are still dropped at the
webhook; this lays the ground for them.

### Added

- `conversations` table and `Conversation` model, keyed by user, chat and
  forum topic (`thread_id`, 0 outside a topic), holding `state` and `menu`.
- `wHook()->conversation()`, `chatId()` and `threadId()`, bound from the
  update's chat and topic. Updates without a chat (inline queries, presses
  on inline-mode messages) belong to the private chat.
- `BotUser::conversations()`, `conversationIn($chatId, $threadId = 0)` and
  `privateConversation()`.
- `WebhookContext` carries `chat_id` and `thread_id`, so a queued job
  resumes in the right conversation. A payload queued before this release
  falls back to the private chat.

### Changed

- `ResolvesKeyboard::for()` takes a `Conversation`. `replyKeyboard()` takes
  a conversation, or a user meaning their private chat.
- `Webhook::setUser()` takes the conversation to bind; without one it binds
  the user's private chat, as `runForUser()` does.
- `Testing\TestCase::makeBotUser()` puts `state` and `menu` attributes on
  the user's private conversation.

### Removed

- `bot_users.state` and `bot_users.menu` (moved into each user's private
  conversation by a migration).
- `BotUser::changeState()` and `BotUser::addParamToState()`.

### Upgrading

1. Where the update's own flow is meant, replace `wHook()->user()->state`,
   `->changeState()` and `->addParamToState()` with the same calls on
   `wHook()->conversation()`, and `wHook()->user()->update(['menu' => …])`
   with `wHook()->conversation()->changeMenu(…)`.
2. Where another user is meant (an admin acting on a user, a job), call
   them on `$user->privateConversation()`.
3. Custom `ResolvesKeyboard` implementations take a `Conversation`.

## [0.17.1] - 2026-10-12

### Fixed

- Messages and button presses from groups, supergroups and channels are
  acknowledged and dropped before anything runs. They used to run against
  the sender's private-chat state (a `/command@bot` in a group cancelled
  their half-finished DM flow, and group text could be read as its next
  answer) and every reply went to their DM. Inline queries and presses on
  inline-mode messages are unaffected. Groups get real support with
  per-chat state later.

## [0.17.0] - 2026-10-12

Access moves from numeric power levels to named permissions. This release is
breaking: every handler that set `protected int $perm` must name a
permission instead, and the app moves its existing admins over itself (see
**Upgrading**).

### Added

- `AuthorizesAccess` contract, which every bus checks through. The default
  `DefaultAuthorizer` lets anyone use a handler without a permission, and
  only the bot owner and the configured developer use the rest. A role
  package (tbe-access) rebinds it to grant permissions to other users.
- Handlers name the permission they need with
  `protected ?string $permission`; `null` (the default) is open to everyone.
- `PermissionRegistry` (`permissionRegistry()`) for packages to declare
  their permission names, labels and groups.
- Helpers `authorizer()`, `canUseHandler()`, `handlerPermission()` and
  `isPublicHandler()`.
- `DefaultAuthorizer::isOwnerOrDeveloper()`, public so a role package shares
  the same owner and developer check.
- `essence.admin_panel` (the admin panel key) and `essence.admin_alerts`
  (who besides the owner gets `adminAlert()`) permissions.
- `ResolvesKeyboard` contract and a `replyKeyboard(?BotUser $user = null)`
  helper that delegates to it, so an app or companion can replace the reply
  keyboard without touching any call site. Without a user it builds the
  keyboard for the webhook's user. The default `DefaultKeyboardResolver` keeps the old precedence
  (state answer keyboard, then Cancel, then the user's menu).
- Named menus under `tbe-essence.keyboard.menus`. Any name stored in
  `bot_users.menu` works, and an unknown one falls back to `main`.
- `tbe-essence.keyboard.enabled` (default `true`). When `false`, no menus or
  automatic Cancel key are shown and any keyboard on screen is removed. A
  state answer's own keyboard is still shown. `keyboard.resize` and
  `keyboard.one_time` replace the hardcoded flags.
- `tbe:make:*` commands take `--permission=`; with `--admin` alone the
  generated handler asks for `app.<snake_name>`.
- `Testing\TestCase::grant($user, ...$permissions)` lets a test give a plain
  user permissions without a role package.

### Changed

- `bot_users.menu` is a string column instead of `enum('main','admin')`.
- `hasAccess()` takes the permission name (`?string`) and no longer has a
  default.
- Every handler interface declares `getPermission(): ?string` in place of
  `getPerm(): int`.
- `adminAlert()` reaches the owner plus whoever `AuthorizesAccess::usersWith()`
  returns for `essence.admin_alerts`.
- `StateAnswerBus::keyboardFor()` takes an optional user to check access for.
- The keyboard and access contracts are bound with `bindIf()`, so a companion
  that registers before essence (package discovery goes by name) keeps its
  binding.
- Menu keys are filtered against the user the keyboard is built for, not the
  webhook's current user, so building a keyboard outside a webhook no longer
  aborts. A row whose keys are all hidden is left out.

### Removed

- `bot_users.power` (dropped by a migration), the `Roles` enum,
  `BotUser::$role`, and the `tbe::general.roles` strings.
- `$perm` and `getPerm()` on every handler base class and interface.
- `BotUser::getKeyboard()`: call `replyKeyboard()` (or `replyKeyboard($user)`
  outside a webhook) instead.
- The bot admins screen (`BotAdminsKey`, `BotAdminsQuery`, `BotAdminsAnswer`,
  `BotAdminsFeature` and `tbe::bot_admins`). Granting access to other users
  belongs to a role package.
- The pre-0.17 `tbe-essence.keyboard.member` / `keyboard.admin` config keys.

### Upgrading

1. Replace `protected int $perm = Roles::ADMIN->value;` with
   `protected ?string $permission = 'your.permission';` and register the
   name with `permissionRegistry()->register()` in a service provider.
   Delete `$perm = Roles::MEMBER->value` / `$perm = 0` lines. A handler left
   with only `$perm` becomes public, so search for `$perm` before
   upgrading.
2. Replace `wHook()->user()->getKeyboard()` with `replyKeyboard()` and
   `$user->getKeyboard()` with `replyKeyboard($user)`.
3. Move `tbe-essence.keyboard.member` / `.admin` under
   `tbe-essence.keyboard.menus.main` / `.admin`, and drop `BotAdminsKey`
   from your menus.
4. Without a role package, only the owner and developer reach admin
   handlers. To keep other admins, install tbe-access and, in an app
   migration timestamped before `2026_10_12_120000_drop_power_from_bot_users`,
   give users with `power >= 100` a role holding `*`.

## [0.16.4] - 2026-10-01

### Added

- Commands take a `protected int $priority` (default `0`). The command bus
  returns commands highest priority first, which sets their order in the
  Telegram menu and in `/help`; commands with equal priority keep their
  registration order.

### Removed

- The `tbe-essence.commands` config key. Nothing has read it since 0.16.2;
  order commands with `$priority` instead.

## [0.16.3] - 2026-10-01

### Changed

- Reworded the user-facing English and Persian strings to read more naturally; no keys or placeholders changed.

### Fixed

- The inline lock and cancel buttons a `MessageMeta` puts on a message ("Locked For
  Action", "Cancel") were hard-coded in English. They are translated now, in English
  and Persian, and follow the user's locale. A label passed to `lockAction()` or
  `cancelableLockAction()` is still used as given.

## [0.16.2] - 2026-09-29

### Fixed

- The bot command menu is now built from the commands registered on the
  command bus (member-level, enabled ones) instead of
  `tbe-essence.commands`, which held only `/help`. The app's own commands,
  such as `/start`, were never published to Telegram.

## [0.16.1] - 2026-09-29

### Fixed

- Bots created or re-tokened through the `/bots` API now get their command
  menu registered; before, only `tbe:set-webhook` did, so new bots showed no
  commands.
- `tbe:set-webhook --all` reports a bot that fails (for example a revoked
  token) and carries on with the rest, exiting non-zero at the end.

## [0.16.0] - 2026-09-28

### Changed

- `tbeLog()` routes each package to its own channel out of the box:
  `billing` (billing, the gateways, user-wallet, affiliates), `activity`
  (user-management), `announcements`, `settings`, and `essence` for any tag
  without a route. `->audit()` entries go to `admin-audit`. Every channel
  the app's `config/logging.php` does not define is registered as a daily
  file, `storage/logs/<name>.log`, kept 14 days, or as listed in
  `logging.retention_days` (90 for `billing` and `admin-audit`). A channel
  the app defines is left alone.
- A `logging` block in the app's `config/tbe-essence.php` is merged over
  the package's rather than replacing it: key by key, and `channels` and
  `retention_days` entry by entry, so the app lists only the routes it adds
  or changes.
- **Breaking:** `TBE_LOG_CHANNEL` and `TBE_LOG_AUDIT_CHANNEL` are gone. Set
  `logging.channel`, `logging.channels` and `logging.audit_channel` in the
  published `config/tbe-essence.php` instead.

### Removed

- The Zirgozar gateway: its controller, the `invoice.zirgozar.*` routes and
  its `bot_settings` strings. The service has shut down, and the controller
  no longer ran (it referenced models that had moved out of essence).

## [0.15.0] - 2026-09-27

### Added

- Text matchers: a handler for a message that no reply key, command or state
  answer claimed, matched by rule rather than by an exact button label.
  Subclass `TextMatcher`, set `$pattern` for a plain regex or override
  `matches(Message $message)` to judge the whole message (text, caption,
  entities, attachments), and put it under `app/Telegram/TextMatchers/`
  (`tbe:make:text-matcher` scaffolds one). The first registered enabled
  matcher that matches wins; one the user has no access to is skipped, and
  the message falls through to the invalid-request reply if nothing else
  takes it. A matcher never runs ahead of a reply key or a state answer, so
  it cannot steal a button press or a form answer. `urls()` returns the URLs
  Telegram itself recognised (`url` and `text_link` entities, or the caption's
  for a media message) and `text()` the text or caption. Fires
  `BotTextMatcherHandled`.
- `adminAlert($key, $text, ?$bot, ?$throttle)`: tells a bot's owner and every
  reachable, unsuspended admin about something only they can fix (a setting
  that stopped working, a permission the bot lost). Throttled per bot and key
  for `admin_alerts.throttle` seconds (6h by default), so a condition seen on
  every update reaches each admin once per window. A Closure `$text` is
  rendered in the bot's locale. Works outside a webhook (queued jobs, the
  scheduler) by building the bot's own API client. Bugs still go to the
  developer through `exceptionReport()`.
- `tbeLog()->audit()`: logs what an admin or the owner did to
  `logging.audit_channel` (`TBE_LOG_AUDIT_CHANNEL`), flagged `audit: true`,
  falling back to the package's channel when unset.
- `tbeLog()->for($botUser)`: binds an entry to a bot user (bot_id, user_id,
  username), for code that knows whose entry it is better than the webhook
  does: a queued job, a gateway callback.
- `logging.channels`: per-package channel overrides keyed by the `tbeLog()`
  tag, e.g. `['billing' => 'payments']`.

### Changed

- Every `tbeLog()` message is prefixed with its subject,
  `bot#3 user#12345 @alice | Invoice paid`, from the `bot_id`, `user_id`
  and `username` context keys, so a line says whose it is without expanding
  it. Outside a webhook, use `for($botUser)` to get the same prefix.
  The webhook context now also carries `username`.
- `tbeLog()` fills PSR-3 `{placeholders}` in the message from the context
  itself (`'Invoice #{invoice_id} paid'`), whatever the host channel's
  `replace_placeholders` setting, so messages can name the ids they are
  about without concatenation.

### Fixed

- The webhook no longer calls `str_starts_with()` on a null `text` when a
  message carries no text (a photo, a sticker).

## [0.14.1] - 2026-09-24

### Fixed

- `Webhook::runForUser()` restored the original bot/user by calling
  `setUser()` again, which re-derives `requestState()` from the
  original user object's *current* `state` rather than restoring what
  it was before the callback ran. When the callback runs for that
  same user and the callback (or code before it, in the same request)
  already changed that state - a payment gateway's auto-accept firing
  `InvoicePaid` from inside the paying user's own state-answer handler
  is the real case - the "restore" silently kept the new value instead
  of putting back the old one. `TelegramWebhookController` reads
  `requestState()` right after the handler returns to build
  `BotStateAnswerHandled`, so a null there crashed with a `TypeError`.

## [0.14.0] - 2026-09-22

### Removed

- **Breaking:** `DoneLimited`, `CannotSetItAsDone`, and `HidesDone` are gone.
  They existed to mark a resource incomplete while a multi-step creation
  flow filled it in field by field, saving a partial row after each answer.
  Forms replaces that: nothing is written until the summary is confirmed, so
  there is no longer a partial state to guard against. A host app still
  filtering on a `done` column should drop the column and the filter.

## [0.13.0] - 2026-09-20

### Added

- Forms: a reusable multi-step data-collection engine. Subclass `Form`, list
  `Text` and `Choice` steps, implement `onComplete()`, register it with
  `formRegistry()` (or `loadForms()`) and start it with `MyForm::start($ctx)`.
  The chat is a plain conversation: each step is a prompt carrying its own
  reply keyboard (Back / Next / Skip / Cancel), and the bot answers back with
  what it understood before asking the next question. Nothing is edited or
  deleted, since Telegram cannot edit a message that carries a reply keyboard
  and refuses to delete one older than 48 hours; only inline messages are
  edited (choice paging, the starting message). Inline buttons page dynamic
  choices, and a summary step confirms before anything is written. A Finish
  button appears once only optional steps are left and jumps to the summary.
  Steps can be conditional
  (`when`), depend on each other (`dependsOn`, re-validation of later answers
  after a change), be skippable, validate with Laravel rules, and show an
  automatic Required/Optional marker plus a hint derived from the rules. Forms
  expire after 24h idle by default; a step whose question depends on an
  earlier answer sets it with `prompt()`. Ships an `en` and `fa` `forms` lang
  file.
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
