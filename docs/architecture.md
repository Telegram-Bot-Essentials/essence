# Architecture

## Overview

Telegram Bot Essentials routes every webhook update through a small set of buses. Each bus maps an encoded string (or user state) to a class method with dependency injection for Eloquent models.

```
Telegram Update
      │
      ▼
TelegramBotAuthentication (middleware)
  • Resolve bot tenant
  • Verify secret_token
  • Load Update into wHook()
  • Find or create BotUser
      │
      ▼
TelegramWebhookController
      │
      ├── fires BotUpdateReceived
      │
      ├── message + starts with "/"  →  Commands  →  fires BotCommandHandled
      │                                               └── /start <x>  →  fires BotDeepLinkReceived
      ├── message + keyboard text    →  ReplyKeyBus    →  fires BotReplyKeyHandled
      ├── message + user has state   →  StateAnswerBus →  fires BotStateAnswerHandled
      ├── nothing matched            →  fires BotUpdateUnhandled
      ├── callback_query             →  CallbackQueryBus  →  fires BotCallbackQueryHandled
      └── inline_query               →  InlineQueryBus    →  fires BotInlineQueryHandled
```

See [Events & Listeners](events.md) for the full event reference.

Source: `src/Http/Controllers/TelegramWebhookController.php`

## The Webhook Context (`wHook()`)

`wHook()` returns a request-scoped singleton (`TelegramBotEssentials\Essence\Support\Webhook`) holding:

| Property | Description |
|----------|-------------|
| `api()` | Telegram `Api` instance for the current bot |
| `update()` | Current Telegram `Update` object |
| `bot()` | Current `Bot` model (tenant) |
| `user()` | Current `BotUser` model |
| `requestState()` | Snapshot of `bot_users.state` when the user was loaded |
| `peerId()` | Telegram user/chat ID from the update |

Use `wHook()` anywhere in handlers instead of passing context through every method.

The context can also be exported/imported for queued jobs via `exportContext()` and `importContext()` (`src/Support/Webhook.php`).

## Encoding Formats

Two parallel encoding schemes exist. Both use `TYPE#method` with optional parameters.

### Callback data (inline buttons)

Used in `callback_data` on inline keyboard buttons. **Hard limit: 64 bytes** (Telegram API).

```
BOTADMNS#add_admin
BOTADMNS#delete_admin?42
```

Helpers: `encodeCallback()`, `decodeCallback()` in `src/helpers.php`.

Params are positional (not named) — resolved by index into handler method parameters.

### Answer state (user input routing)

Stored in `bot_users.state`. No Telegram size limit (lives in your database).

```
BOTADMNS#add_admin?message_meta_id=12
PRODUCTSMANAGEMENT#add?target_field=title&state_data=7
```

Helpers: `encodeAnswerState()`, `decodeAnswerState()` in `src/helpers.php`.

Params are named query-string key/value pairs — resolved by name into handler method parameters.

## Building Blocks

### Commands

Standard [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) commands. Register in `config/tbe-essence.php` under `commands`.

Commands take priority over everything else. When a command runs, any active user state is cancelled first.

### ReplyKeys

Keyboard buttons shown at the bottom of the chat. Registered via `config/tbe-essence.php` → `keyboard` and/or `loadReplyKeys()`.

When a ReplyKey is pressed:
1. Any active user state is cleared (`cancelOldProcess`)
2. The key's `handle()` runs
3. The previous state's `cancel()` method is invoked on the StateAnswer

While a user has an active state, the keyboard shows **Cancel Process** instead of the normal menu.

### CallbackQueries

Handle inline button presses. Each class declares:

- `$type` — uppercase identifier used in `encodeCallback()`
- `$perm` — minimum `BotUser.power` required (`Roles` enum)
- Methods matching callback method names (snake_case in encoding, resolved to camelCase)

Model parameters are resolved by **position** from callback params:

```php
// callback_data: BOTADMNS#delete_admin?42
public function deleteAdmin(BotUser $botUser): void
```

### StateAnswers

Handle text/media input while `bot_users.state` is set. Same structure as CallbackQueries but:

- Params are **named** (from query string)
- `$allowedFields` controls which message fields trigger the handler (`text`, `photo`, `document`)
- Optional `cancel()` method for cleanup when the flow is interrupted

See [State & StateData](state-and-statedata.md) for the full input-flow pattern.

### Features

Static classes that return `TelegramResponse` objects. They build message text and inline keyboards but do not handle updates directly.

Features are called from CallbackQueries or StateAnswers:

```php
BotAdminsFeature::menu()->update();   // edit current callback message
BotAdminsFeature::menu()->send();     // send new message
```

`TelegramResponse` supports:
- `send()` — new message
- `update()` — edit callback message
- `answer()` — toast on callback query
- `messageMetaModel()` — attach MessageMeta to a model

Source: `src/Telegram/TelegramResponse.php`, `src/Telegram/Features/BotAdminsFeature.php`

### MessageMeta

Tracks a Telegram message so it can be locked, reverted, or updated during multi-step flows.

Common operations:
- `makeWithCurrentMessage()` — snapshot the callback message
- `lockAction()` — replace inline keyboard with a lock button
- `continueAction()` — delete lock message and restore original
- `revertAction()` — edit message back to original content
- `updateAndContinueAction(TelegramResponse)` — replace with new content

MessageMeta morphs to an `action` model (e.g. `StateData`, an order, a product).

Source: `src/Models/MessageMeta.php`

### StateData

Separate JSON storage for multi-step forms and cross-app payloads. See [State & StateData](state-and-statedata.md).

## Multi-Tenancy

Each `Bot` is a Stancl Tenancy tenant (`bots` table). The webhook route uses `InitializeTenancyByPath` middleware with `{bot}` as the tenant identifier (`unique_id`).

`BelongsToTenant::$tenantIdColumn` is set to `bot_id`, so models like `BotUser` are scoped per bot.

## Access Control

`hasAccess(?int $power)` checks:
1. `BotUser.power >= $power`
2. User is the bot owner
3. User matches `config('tbe-essence.developer.peer_id')`

Roles enum (`src/Enums/Roles.php`):
- `ADMIN = 100`
- `MODERATOR = 10`
- `MEMBER = 0`

## Error Handling

Uncaught exceptions in the webhook are handled by `exceptionHandler()`. `exceptionReport()` additionally sends trace files and update JSON to `config('tbe-essence.bug_report.telegram_chat_id')` when configured.

## Request Processing Priority

For **messages** (in order):
1. Commands (`/start`, etc.)
2. ReplyKeys (keyboard button text match)
3. StateAnswers (if `bot_users.state` is set)

If none match → "request is invalid" response.

For **callback queries**:
- CallbackQueryBus only (state is not checked)
