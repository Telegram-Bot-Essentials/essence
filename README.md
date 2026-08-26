# Telegram Bot Essentials (Essence)

[![tests](https://github.com/Elyar0/telegram-bot-essentials/actions/workflows/tests.yml/badge.svg)](https://github.com/Elyar0/telegram-bot-essentials/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A Laravel package for running many Telegram bots from one codebase, each bot
its own tenant. Instead of one giant update handler per bot, incoming
webhook updates are dispatched through typed buses - **Commands**,
**ReplyKeys**, **CallbackQueries**, and **StateAnswers** - so a
multi-tenant, multi-locale bot platform stays organized as it grows past a
handful of features.

## Requirements

- PHP ^8.3
- Laravel ^12 or ^13
- [irazasyed/telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) ^3.15
- [stancl/tenancy](https://tenancyforlaravel.com/) ^3.9 (each bot is a tenant)

## Installation

```bash
composer require telegram-bot-essentials/essence
```

Publish config and translations:

```bash
php artisan tbe:install
php artisan migrate
```

Initialize your main bot and set the webhook:

```bash
php artisan tbe:singlebot:init
php artisan tbe:set-webhook
```

Configure `config/tbe-essence.php` and `.env` values (`MAIN_TELEGRAM_BOT_TOKEN`, `MAIN_UNIQUE_ID`, `MAIN_ADMIN_CHAT_ID`, etc.) before running init.

## Webhook URL

Each bot receives updates at:

```
POST /api/{bot_unique_id}/telegram/bot/webhook
```

Authentication uses the Telegram `secret_token` header, verified against the hashed token stored on the `bots` table when you run `tbe:set-webhook`.

## Core Concepts

| Concept | Trigger | Purpose |
|---------|---------|---------|
| **Commands** | `/start`, `/help`, … | Slash-command handlers |
| **ReplyKeys** | Keyboard button text | Main menu and navigation |
| **CallbackQueries** | Inline button presses | Menus, actions, starting input flows |
| **StateAnswers** | Text/media while user has active state | Multi-step input, form wizards |
| **Features** | Called from queries/answers | Build `TelegramResponse` messages |
| **MessageMeta** | Associated with messages | Lock, revert, and update bot messages |
| **StateData** | Referenced by ID | JSON payload storage for wizards and deeplinks |

## Project Layout (in your Laravel app)

```
app/Telegram/
├── CallbackQueries/
│   ├── Admin/
│   └── Member/
├── StateAnswers/
│   ├── Admin/
│   └── Member/
├── ReplyKeys/
│   ├── Admin/
│   └── Member/
├── Features/
│   ├── Admin/
│   └── Member/
└── Commands/
```

Handlers are discovered from `app/Telegram/**` once per process/worker at boot, after every companion package has registered its own - so your app's handlers always win a naming collision. Package defaults are loaded from `vendor/telegram-bot-essentials/essence/src/Telegram/`.

## Documentation

Full documentation lives in the [essence-docs](https://github.com/Elyar0/essence-docs)
site, which is the single source of truth:

- [Architecture](https://github.com/Elyar0/essence-docs/blob/main/docs/core/architecture.md) — webhook flow, buses, encoding formats
- [State & StateData](https://github.com/Elyar0/essence-docs/blob/main/docs/core/state-and-statedata.md) — `bot_users.state` vs `state_data` table
- [Extending](https://github.com/Elyar0/essence-docs/blob/main/docs/core/extending.md) — building CallbackQueries, StateAnswers, Features
- [Commands](https://github.com/Elyar0/essence-docs/blob/main/docs/core/commands.md) — CommandBus, aliases, programmatic routing
- [Events & Listeners](https://github.com/Elyar0/essence-docs/blob/main/docs/core/events.md) — the bot event bus
- [Reference](https://github.com/Elyar0/essence-docs/blob/main/docs/core/reference.md) — helpers, config, database, artisan commands

## Artisan Generators

```bash
php artisan make:callback-query Admin/MyFeatureQuery
php artisan make:state-answer Member/MyFeatureAnswer
php artisan make:reply-key Member/MyFeatureKey
php artisan make:feature Admin/MyFeatureFeature
php artisan make:command MyStartCommand
```

## Testing

Extend `TelegramBotEssentials\Essence\Testing\TestCase` (a Testbench base essence ships in `src/`) instead of wiring up Testbench yourself - every companion package already depends on essence, so no extra dependency is needed:

```php
use TelegramBotEssentials\Essence\Testing\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('replies when the button is pressed', function () {
    $bot = $this->makeBot();

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('Main Menu 🔰'))
        ->assertOk();

    $this->assertTelegramSent(fn ($request) => str_contains($request['text'], 'Main Menu loaded'));
});
```

`postWebhookUpdate()` sends a real request through routing, `TelegramBotAuthentication`, and the controller - not a hand-wired shortcut. Outbound Telegram API calls are faked automatically (`Http::fake()` in `setUp()`): `LaravelHttpClient`, essence's default `http_client_handler`, routes every SDK call through `Illuminate\Support\Facades\Http`, so no bespoke SDK mocking is needed.

## Related Packages

Essence is the core framework. Optional companion packages extend it:

- `telegram-bot-essentials/billing` — invoices and payments
- `telegram-bot-essentials/settings` — bot settings
- `telegram-bot-essentials/user-wallet` — user wallet/credits

Register their CallbackQueries and StateAnswers in each package's service provider via `callbackQueryBus()` and `stateAnswerBus()`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for local setup, the test/lint/analyse commands CI runs, and commit conventions.

## License

[MIT](LICENSE)
